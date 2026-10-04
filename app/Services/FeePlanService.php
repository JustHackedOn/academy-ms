<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class FeePlanService
{
    /**
     * Generate installment previews for UI or creation.
     */
    public static function calculateSchedule(
        float $netFee,
        string $feeType,
        string $startDate,
        int $installmentsCount = 4
    ): array {
        $schedule = [];
        $baseDate = Carbon::parse($startDate);

        if ($feeType === 'full' || $installmentsCount <= 1) {
            $schedule[] = [
                'number' => 1,
                'due_date' => $baseDate->format('Y-m-d'),
                'amount' => round($netFee, 2),
            ];
            return $schedule;
        }

        // Split equally with remainder on the last installment
        $equalBase = floor(($netFee / $installmentsCount) * 100) / 100;
        $allocatedSoFar = 0;

        for ($i = 1; $i <= $installmentsCount; $i++) {
            $dueDate = $baseDate->copy()->addMonthsNoOverflow($i - 1)->format('Y-m-d');

            if ($i === $installmentsCount) {
                $amount = round($netFee - $allocatedSoFar, 2);
            } else {
                $amount = $equalBase;
                $allocatedSoFar += $amount;
            }

            $schedule[] = [
                'number' => $i,
                'due_date' => $dueDate,
                'amount' => $amount,
            ];
        }

        return $schedule;
    }

    /**
     * Validate and create installments for a student.
     * @throws ValidationException
     */
    public static function createInstallments(Student $student, array $installmentsData): void
    {
        $sum = array_sum(array_column($installmentsData, 'amount'));
        if (abs($sum - (float)$student->net_fee) > 0.05) {
            throw ValidationException::withMessages([
                'installments' => 'The sum of installment amounts (' . money($sum, 2) . ") must equal the student's net fee (" . money($student->net_fee, 2) . ').'
            ]);
        }

        foreach ($installmentsData as $index => $item) {
            $dueDate = Carbon::parse($item['due_date']);
            $isPast = $dueDate->startOfDay()->lt(now()->startOfDay());

            Installment::create([
                'student_id' => $student->id,
                'number' => $index + 1,
                'due_date' => $dueDate->format('Y-m-d'),
                'amount' => $item['amount'],
                'paid_amount' => 0,
                'status' => $isPast ? 'overdue' : 'pending',
            ]);
        }
    }

    /**
     * Recalculate unpaid installments when net fee is modified after payments have been recorded.
     */
    public static function recalculateUnpaidInstallments(Student $student, float $newNetFee): void
    {
        // Preserve installments that have received any payment (paid or partial)
        $keptInstallments = $student->installments()
            ->where(function ($q) {
                $q->where('status', 'paid')
                  ->orWhere('paid_amount', '>', 0);
            })
            ->get();

        $totalKeptAmount = (float) $keptInstallments->sum('amount');
        $totalKeptPaid = (float) $keptInstallments->sum('paid_amount');

        $remainingToPlan = max(0, $newNetFee - $totalKeptAmount);

        // Only delete installments that have zero payments
        $fullyUnpaid = $student->installments()
            ->where('status', '!=', 'paid')
            ->where('paid_amount', '<=', 0)
            ->get();

        $unpaidCount = max(1, $fullyUnpaid->count());

        $student->installments()
            ->where('status', '!=', 'paid')
            ->where('paid_amount', '<=', 0)
            ->delete();

        if ($remainingToPlan <= 0) {
            return;
        }

        $nextNumber = ($keptInstallments->max('number') ?? 0) + 1;
        $baseDate = now();

        $schedule = self::calculateSchedule($remainingToPlan, 'installments', $baseDate->format('Y-m-d'), $unpaidCount);

        foreach ($schedule as $idx => $item) {
            Installment::create([
                'student_id' => $student->id,
                'number' => $nextNumber + $idx,
                'due_date' => $item['due_date'],
                'amount' => $item['amount'],
                'paid_amount' => 0,
                'status' => 'pending',
            ]);
        }
    }
}
