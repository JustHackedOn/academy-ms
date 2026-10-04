<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\User;
use App\Services\DashboardStatsService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsAcademy;
use Tests\TestCase;

class MonthlyReportTest extends TestCase
{
    use BuildsAcademy, RefreshDatabase;

    public function test_monthly_summary_reports_expected_received_expenses_and_net_profit(): void
    {
        $batch = $this->makeBatch();
        $student = $this->makeStudent($batch, ['total_fee' => 6000]);
        $this->makeInstallments($student, [3000, 3000]);

        $firstInstallment = $student->installments()->orderBy('number')->first();

        Payment::create([
            'student_id' => $student->id,
            'installment_id' => $firstInstallment->id,
            'amount' => 3000,
            'paid_on' => now()->toDateString(),
            'for_month' => now()->format('Y-m'),
            'method' => 'cash',
            'receipt_no' => 'RCPT-'.now()->format('Y').'-0001',
        ]);

        $firstInstallment->update(['paid_amount' => 3000, 'status' => 'paid']);

        Expense::create([
            'title' => 'Internet bill',
            'category' => 'Utilities',
            'amount' => 500,
            'spent_on' => now()->toDateString(),
        ]);

        $summary = ReportService::monthlySummary(now()->format('Y-m'));

        $this->assertEqualsWithDelta(3000, $summary['expected'], 0.01);
        $this->assertEqualsWithDelta(3000, $summary['received'], 0.01);
        $this->assertEqualsWithDelta(500, $summary['expenses'], 0.01);
        $this->assertEqualsWithDelta(2500, $summary['net_profit'], 0.01);
        $this->assertSame(1, $summary['payments_count']);
        $this->assertEqualsWithDelta(100.0, $summary['collection_rate'], 0.01);
        $this->assertNotEmpty($summary['batch_rows']);
    }

    public function test_batch_summary_totals_match_the_students_in_the_batch(): void
    {
        $batch = $this->makeBatch();
        $this->makeStudent($batch, ['total_fee' => 8000]);
        $this->makeStudent($batch, ['total_fee' => 4000]);

        $report = ReportService::batchSummary();

        $this->assertCount(1, $report['rows']);
        $this->assertEqualsWithDelta(12000, $report['totals']['expected'], 0.01);
        $this->assertEqualsWithDelta(0, $report['totals']['collected'], 0.01);
        $this->assertEqualsWithDelta(12000, $report['totals']['remaining'], 0.01);
        $this->assertSame(2, $report['totals']['students']);
    }

    public function test_dashboard_stats_expose_the_monthly_kpis(): void
    {
        $batch = $this->makeBatch();
        $student = $this->makeStudent($batch, ['total_fee' => 6000]);
        $this->makeInstallments($student, [6000]);

        Payment::create([
            'student_id' => $student->id,
            'amount' => 6000,
            'paid_on' => now()->toDateString(),
            'for_month' => now()->format('Y-m'),
            'method' => 'cash',
            'receipt_no' => 'RCPT-'.now()->format('Y').'-0001',
        ]);

        $stats = DashboardStatsService::getStats(now()->format('Y-m'), false);

        $this->assertEqualsWithDelta(6000, $stats['expected'], 0.01);
        $this->assertEqualsWithDelta(6000, $stats['received'], 0.01);
        $this->assertEqualsWithDelta(100, $stats['collection_rate'], 0.01);
        $this->assertSame(1, $stats['active_students']);
    }

    public function test_reports_page_renders_for_an_authenticated_user(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Reports');
    }

    public function test_student_csv_export_streams_the_current_students(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $batch = $this->makeBatch();
        $this->makeStudent($batch, ['name' => 'Export Target', 'total_fee' => 5000]);

        $response = $this->actingAs($user)->get(route('reports.export', ['type' => 'students']));

        $response->assertOk();
        $this->assertStringContainsString('Export Target', $response->streamedContent());
    }
}
