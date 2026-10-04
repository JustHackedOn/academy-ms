<?php

namespace Tests\Feature;

use App\Services\FeePlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsAcademy;
use Tests\TestCase;

class FeePlanTest extends TestCase
{
    use BuildsAcademy, RefreshDatabase;

    public function test_schedule_splits_evenly_and_puts_remainder_on_the_last_installment(): void
    {
        $schedule = FeePlanService::calculateSchedule(10000, 'installments', '2026-01-01', 3);

        $this->assertCount(3, $schedule);
        $this->assertSame([1, 2, 3], array_column($schedule, 'number'));
        $this->assertSame(3333.33, $schedule[0]['amount']);
        $this->assertSame(3333.33, $schedule[1]['amount']);
        $this->assertSame(3333.34, $schedule[2]['amount']);

        $this->assertEqualsWithDelta(10000, array_sum(array_column($schedule, 'amount')), 0.001);
    }

    public function test_schedule_due_dates_are_one_month_apart(): void
    {
        $schedule = FeePlanService::calculateSchedule(12000, 'installments', '2026-01-15', 4);

        $this->assertSame(
            ['2026-01-15', '2026-02-15', '2026-03-15', '2026-04-15'],
            array_column($schedule, 'due_date')
        );
    }

    public function test_full_payment_plan_creates_a_single_installment(): void
    {
        $schedule = FeePlanService::calculateSchedule(50000, 'full', '2026-01-01', 4);

        $this->assertCount(1, $schedule);
        $this->assertSame(50000.0, $schedule[0]['amount']);
        $this->assertSame(1, $schedule[0]['number']);
    }

    public function test_installments_whose_sum_does_not_match_net_fee_are_rejected(): void
    {
        $batch = $this->makeBatch();
        $student = $this->makeStudent($batch, ['total_fee' => 10000]);

        $this->expectException(ValidationException::class);

        FeePlanService::createInstallments($student, [
            ['due_date' => now()->addDays(5)->toDateString(), 'amount' => 4000],
            ['due_date' => now()->addMonths(1)->toDateString(), 'amount' => 5000],
        ]);
    }

    public function test_matching_installment_sum_is_persisted(): void
    {
        $batch = $this->makeBatch();
        $student = $this->makeStudent($batch, ['total_fee' => 10000]);

        FeePlanService::createInstallments($student, [
            ['due_date' => now()->addDays(5)->toDateString(), 'amount' => 5000],
            ['due_date' => now()->addMonths(1)->toDateString(), 'amount' => 5000],
        ]);

        $this->assertSame(2, $student->installments()->count());
        $this->assertEqualsWithDelta(10000, $student->installments()->sum('amount'), 0.01);
    }

    public function test_installments_with_a_past_due_date_start_life_overdue(): void
    {
        $batch = $this->makeBatch();
        $student = $this->makeStudent($batch, ['total_fee' => 6000]);

        FeePlanService::createInstallments($student, [
            ['due_date' => now()->subDays(10)->toDateString(), 'amount' => 3000],
            ['due_date' => now()->addDays(10)->toDateString(), 'amount' => 3000],
        ]);

        $this->assertSame('overdue', $student->installments()->where('number', 1)->value('status'));
        $this->assertSame('pending', $student->installments()->where('number', 2)->value('status'));
    }

    public function test_student_balance_is_the_gap_between_net_fee_and_payments(): void
    {
        $batch = $this->makeBatch();
        $student = $this->makeStudent($batch, ['total_fee' => 9000, 'discount' => 1000]);
        $this->makeInstallments($student, [4000, 4000]);

        $this->assertEqualsWithDelta(8000, $student->net_fee, 0.01);
        $this->assertEqualsWithDelta(8000, $student->remaining_balance, 0.01);
    }
}
