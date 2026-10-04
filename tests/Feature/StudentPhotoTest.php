<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsAcademy;
use Tests\TestCase;

class StudentPhotoTest extends TestCase
{
    use BuildsAcademy, RefreshDatabase;

    public function test_admin_can_upload_a_student_photo_during_enrollment(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $batch = $this->makeBatch();

        $response = $this->actingAs($admin)->post('/students', [
            'batch_id' => $batch->id,
            'name' => 'Photo Student',
            'photo' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
            'phone' => '923001234567',
            'fee_type' => 'installments',
            'total_fee' => 10000,
            'discount' => 0,
            'join_date' => now()->toDateString(),
            'status' => 'active',
            'installments' => [
                ['due_date' => now()->addMonth()->toDateString(), 'amount' => 10000],
            ],
        ]);

        $student = Student::where('name', 'Photo Student')->firstOrFail();

        $response->assertRedirect(route('students.show', $student));
        $this->assertNotNull($student->photo);
        Storage::disk('public')->assertExists($student->photo);
    }

    public function test_student_photo_can_be_replaced(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $batch = $this->makeBatch();
        $student = $this->makeStudent($batch, ['photo' => 'students/old.jpg']);
        Storage::disk('public')->put('students/old.jpg', 'old-bytes');

        $this->actingAs($admin)->put('/students/'.$student->id, [
            'batch_id' => $batch->id,
            'name' => $student->name,
            'photo' => UploadedFile::fake()->image('new.jpg', 200, 200),
            'phone' => '923001234567',
            'status' => 'active',
        ])->assertRedirect(route('students.show', $student));

        $student->refresh();

        $this->assertNotSame('students/old.jpg', $student->photo);
        Storage::disk('public')->assertExists($student->photo);
        Storage::disk('public')->assertMissing('students/old.jpg');
    }

    public function test_student_photo_can_be_removed(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $batch = $this->makeBatch();
        $student = $this->makeStudent($batch, ['photo' => 'students/gone.jpg']);
        Storage::disk('public')->put('students/gone.jpg', 'bytes');

        $this->actingAs($admin)->put('/students/'.$student->id, [
            'batch_id' => $batch->id,
            'name' => $student->name,
            'remove_photo' => 1,
            'phone' => '923001234567',
            'status' => 'active',
        ])->assertRedirect(route('students.show', $student));

        $this->assertNull($student->fresh()->photo);
        Storage::disk('public')->assertMissing('students/gone.jpg');
    }

    public function test_invalid_photo_is_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $batch = $this->makeBatch();

        $response = $this->actingAs($admin)->post('/students', [
            'batch_id' => $batch->id,
            'name' => 'Bad Photo Student',
            'photo' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
            'phone' => '923001234567',
            'fee_type' => 'installments',
            'total_fee' => 10000,
            'discount' => 0,
            'join_date' => now()->toDateString(),
            'status' => 'active',
            'installments' => [
                ['due_date' => now()->addMonth()->toDateString(), 'amount' => 10000],
            ],
        ]);

        $this->assertDatabaseMissing('students', ['name' => 'Bad Photo Student']);
        $response->assertSessionHasErrors('photo');
    }

    public function test_students_index_and_show_render_photo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $batch = $this->makeBatch();
        $student = $this->makeStudent($batch, ['photo' => 'students/pic.jpg']);
        Storage::disk('public')->put('students/pic.jpg', 'bytes');

        $this->actingAs($admin)->get('/students')
            ->assertOk()
            ->assertSee('/storage/students/pic.jpg', false);

        $this->actingAs($admin)->get('/students/'.$student->id)
            ->assertOk()
            ->assertSee('/storage/students/pic.jpg', false);
    }
}
