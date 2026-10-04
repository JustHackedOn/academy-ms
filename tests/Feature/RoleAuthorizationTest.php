<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Expense;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsAcademy;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use BuildsAcademy, RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/expenses')->assertRedirect('/login');
        $this->get('/reports')->assertRedirect('/login');
        $this->get('/settings')->assertRedirect('/login');
    }

    public function test_partner_can_record_an_expense(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);

        $this->actingAs($partner)->post('/expenses', [
            'title' => 'Marker pens',
            'category' => 'Equipment',
            'amount' => 250,
            'spent_on' => now()->toDateString(),
        ])->assertRedirect('/expenses');

        $this->assertDatabaseHas('expenses', ['title' => 'Marker pens']);
    }

    public function test_partner_cannot_hard_delete_an_expense(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);

        $expense = Expense::create([
            'title' => 'Tripod',
            'category' => 'Equipment',
            'amount' => 3000,
            'spent_on' => now()->toDateString(),
        ]);

        $this->actingAs($partner)
            ->delete('/expenses/'.$expense->id)
            ->assertForbidden();

        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    public function test_partner_cannot_hard_delete_a_batch(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $batch = $this->makeBatch();

        $this->actingAs($partner)
            ->delete('/batches/'.$batch->id)
            ->assertForbidden();

        $this->assertDatabaseHas('batches', ['id' => $batch->id]);
    }

    public function test_admin_can_hard_delete_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $expense = Expense::create([
            'title' => 'Old chair',
            'category' => 'Equipment',
            'amount' => 1000,
            'spent_on' => now()->toDateString(),
        ]);

        $batch = $this->makeBatch();

        $this->actingAs($admin)
            ->delete('/expenses/'.$expense->id)
            ->assertRedirect('/expenses');

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);

        $this->actingAs($admin)
            ->delete('/batches/'.$batch->id)
            ->assertRedirect('/batches');

        $this->assertSoftDeleted('batches', ['id' => $batch->id]);
    }

    public function test_admin_cannot_delete_a_batch_that_still_has_students(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $batch = $this->makeBatch();
        $this->makeStudent($batch);

        $this->actingAs($admin)
            ->delete('/batches/'.$batch->id)
            ->assertRedirect('/batches');

        $this->assertDatabaseHas('batches', ['id' => $batch->id, 'deleted_at' => null]);
        $this->assertNotNull(session('error'));
    }

    public function test_only_admins_can_manage_portal_users(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);

        $this->actingAs($partner)->post('/settings/users', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'password123',
            'role' => 'admin',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    }

    public function test_partner_cannot_access_settings_or_activity_pages(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);

        $this->actingAs($partner)->get('/settings')->assertForbidden();
        $this->actingAs($partner)->get('/activity')->assertForbidden();
    }

    public function test_partner_cannot_edit_academy_settings(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);

        Setting::set('academy_name', 'Original Academy');

        $this->actingAs($partner)->put('/settings', [
            'academy_name' => 'Partner Updated Academy',
            'currency' => 'PKR',
            'whatsapp_template' => 'Dear {name}, you owe {amount_due}.',
            'whatsapp_template_roman' => 'Pyare {name}, {amount_due} baqi hai.',
        ])->assertForbidden();

        $this->assertSame('Original Academy', Setting::get('academy_name'));
    }

    public function test_admin_can_edit_academy_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/settings', [
            'academy_name' => 'Admin Updated Academy',
            'currency' => 'PKR',
            'whatsapp_template' => 'Dear {name}, you owe {amount_due}.',
            'whatsapp_template_roman' => 'Pyare {name}, {amount_due} baqi hai.',
        ])->assertRedirect('/settings');

        $this->assertSame('Admin Updated Academy', Setting::get('academy_name'));
    }

    public function test_unauthenticated_users_cannot_record_payments(): void
    {
        $batch = Batch::count();

        $this->post('/payments', [
            'student_id' => 1,
            'amount' => 100,
            'paid_on' => now()->toDateString(),
            'method' => 'cash',
        ])->assertRedirect('/login');

        $this->assertSame(0, $batch);
    }
}
