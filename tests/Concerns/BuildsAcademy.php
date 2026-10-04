<?php

namespace Tests\Concerns;

use App\Models\Batch;
use App\Models\Installment;
use App\Models\Student;

/**
 * Tiny builders so feature tests can assemble realistic academy data
 * without depending on the demo seeder.
 */
trait BuildsAcademy
{
    protected function makeBatch(array $overrides = []): Batch
    {
        static $sequence = 0;
        $sequence++;

        return Batch::create(array_merge([
            'name' => 'Test Batch ' . $sequence,
            'code' => 'TST-' . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
            'default_fee' => 40000,
            'duration_months' => 4,
            'start_date' => now()->subMonth()->format('Y-m-d'),
            'end_date' => now()->addMonths(3)->format('Y-m-d'),
            'status' => 'running',
        ], $overrides));
    }

    protected function makeStudent(Batch $batch, array $overrides = []): Student
    {
        $totalFee = (float) ($overrides['total_fee'] ?? 9000);
        $discount = (float) ($overrides['discount'] ?? 0);

        return Student::create(array_merge([
            'batch_id' => $batch->id,
            'name' => 'Test Student',
            'phone' => '923001234567',
            'fee_type' => 'installments',
            'total_fee' => $totalFee,
            'discount' => $discount,
            'net_fee' => max(0, $totalFee - $discount),
            'join_date' => now()->subMonth()->toDateString(),
            'status' => 'active',
        ], $overrides));
    }

    /**
     * Create one installment per amount, one month apart starting today.
     *
     * @param  array<int, float>  $amounts
     */
    protected function makeInstallments(Student $student, array $amounts): void
    {
        foreach ($amounts as $index => $amount) {
            Installment::create([
                'student_id' => $student->id,
                'number' => $index + 1,
                'due_date' => now()->addMonths($index)->toDateString(),
                'amount' => $amount,
                'paid_amount' => 0,
                'status' => 'pending',
            ]);
        }
    }
}
