<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsAcademy;
use Tests\TestCase;

class PageRenderTest extends TestCase
{
    use BuildsAcademy, RefreshDatabase;

    public function test_core_pages_render_for_admin_with_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $batch = $this->makeBatch();
        $student = $this->makeStudent($batch, ['photo' => 'students/smoke.jpg']);
        $this->makeInstallments($student, [2500, 2500, 2500, 2500]);

        $pages = [
            '/dashboard',
            '/students',
            '/students/create',
            '/students/'.$student->id,
            '/students/'.$student->id.'/edit',
            '/batches',
            '/batches/create',
            '/batches/'.$batch->id,
            '/batches/'.$batch->id.'/edit',
            '/payments',
            '/payments/create',
            '/dues',
            '/ledger',
            '/reports',
            '/expenses',
            '/activity',
            '/settings',
        ];

        foreach ($pages as $uri) {
            $this->actingAs($admin)->get($uri)->assertOk($uri);
        }
    }

    public function test_core_pages_render_when_database_is_empty(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['/dashboard', '/students', '/batches', '/payments', '/dues', '/ledger', '/reports', '/expenses'] as $uri) {
            $this->actingAs($admin)->get($uri)->assertOk($uri);
        }
    }

    public function test_role_or_permission_gate_pages_render_for_partner(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);

        foreach (['/dashboard', '/students', '/batches', '/payments', '/dues', '/reports', '/expenses'] as $uri) {
            $this->actingAs($partner)->get($uri)->assertOk($uri);
        }
    }
}
