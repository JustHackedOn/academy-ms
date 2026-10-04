<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentAllocationService
{
    /**
     * Record a payment and allocate to installments.
     * @throws ValidationException
     */
    public static function recordPayment(
        Student $student,
        float $amount,
        string $paidOn,
        string $method,
        ?string $forMonth = null,
        ?string $reference = null,
        ?string $note = null,
        ?int $specificInstallmentId = null
    ): Payment {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.'
            ]);
        }

        $remainingBalance = $student->remaining_balance;
        if (round($amount, 2) > round($remainingBalance, 2)) {
            throw ValidationException::withMessages([
                'amount' => 'Overpayment blocked! The payment amount (' . money($amount, 2) . ') exceeds the student\'s remaining balance of ' . money($remainingBalance, 2) . '.'
            ]);
        }

        $paidDate = Carbon::parse($paidOn);
        $month = $forMonth ?? $paidDate->format('Y-m');

        // Race-safe receipt generation: if two requests grab the same receipt
        // number (unique index violation), retry with the next sequence value.
        $attempt = 0;

        retry: $attempt++;

        try {
            return DB::transaction(function () use (
                $student,
                $amount,
                $paidDate,
                $month,
                $method,
                $reference,
                $note,
                $specificInstallmentId
            ) {
                $receiptNo = Payment::generateReceiptNo();

                $payment = Payment::create([
                    'student_id' => $student->id,
                    'installment_id' => $specificInstallmentId,
                    'amount' => $amount,
                    'paid_on' => $paidDate->format('Y-m-d'),
                    'for_month' => $month,
                    'method' => $method,
                    'reference' => $reference,
                    'note' => $note,
                    'received_by' => auth()->id(),
                    'receipt_no' => $receiptNo,
                ]);


            // Allocation logic:
            if ($specificInstallmentId) {
                // Manual override: allocate to chosen installment first, rest to remaining
                $target = Installment::where('student_id', $student->id)->findOrFail($specificInstallmentId);
                $unpaidInTarget = max(0, (float)$target->amount - (float)$target->paid_amount);
                $allocatedToTarget = min($amount, $unpaidInTarget);

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'installment_id' => $target->id,
                    'amount' => $allocatedToTarget,
                ]);

                $target->paid_amount += $allocatedToTarget;
                $target->refreshStatus();

                $leftover = $amount - $allocatedToTarget;
                if ($leftover > 0) {
                    self::autoAllocateRemainder($student, $payment, $leftover, $target->id);
                }
            } else {
                // Auto-allocation to oldest unpaid/partial installments
                self::autoAllocateRemainder($student, $payment, $amount);
            }

            // Log activity
            ActivityLog::log(
                action: 'recorded_payment',
                subject: $payment,
                newValues: [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'receipt_no' => $receiptNo,
                    'amount' => $amount,
                    'method' => $method,
                    'paid_on' => $paidDate->format('Y-m-d'),
                ]
            );

            return $payment;
            });
        } catch (QueryException $e) {
            // 23000 = unique constraint violation (receipt_no collision).
            if ($e->getCode() === '23000' && $attempt < 5) {
                goto retry;
            }

            throw $e;
        }
    }

    /**
     * Auto-allocate amount across oldest unpaid installments.
     */
    protected static function autoAllocateRemainder(Student $student, Payment $payment, float $amount, ?int $skipInstallmentId = null): void
    {
        $remainingToAllocate = $amount;

        $installments = $student->installments()
            ->when($skipInstallmentId, fn($q) => $q->where('id', '!=', $skipInstallmentId))
            ->where(function ($q) {
                $q->whereColumn('paid_amount', '<', 'amount')
                  ->orWhere('status', '!=', 'paid');
            })
            ->orderBy('number')
            ->get();

        foreach ($installments as $installment) {
            if ($remainingToAllocate <= 0) break;

            $needed = max(0, (float)$installment->amount - (float)$installment->paid_amount);
            if ($needed <= 0) continue;

            $allocateAmount = min($remainingToAllocate, $needed);

            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'installment_id' => $installment->id,
                'amount' => $allocateAmount,
            ]);

            $installment->paid_amount += $allocateAmount;
            $installment->refreshStatus();

            $remainingToAllocate -= $allocateAmount;
        }

        // If primary installment_id on payment is null, associate with first allocation
        if (!$payment->installment_id) {
            $firstAlloc = $payment->allocations()->first();
            if ($firstAlloc) {
                $payment->updateQuietly(['installment_id' => $firstAlloc->installment_id]);
            }
        }
    }

    /**
     * Re-calculate all installments for a student (e.g. after payment deleted/restored/edited).
     */
    public static function recalculateStudentInstallments(Student $student): void
    {
        // 1. Reset all paid_amount on installments to 0
        $student->installments()->update(['paid_amount' => 0]);

        // 2. Fetch all valid non-deleted payments sorted by paid_on
        $payments = $student->payments()->oldest('paid_on')->get();

        // 3. Re-run allocation across installments in order
        $installments = $student->installments()->orderBy('number')->get();

        // Clear existing allocations
        PaymentAllocation::whereIn('payment_id', $payments->pluck('id'))->delete();

        foreach ($payments as $payment) {
            $remainingPayment = (float) $payment->amount;

            foreach ($installments as $inst) {
                if ($remainingPayment <= 0) break;

                $needed = max(0, (float)$inst->amount - (float)$inst->paid_amount);
                if ($needed <= 0) continue;

                $allocate = min($remainingPayment, $needed);

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'installment_id' => $inst->id,
                    'amount' => $allocate,
                ]);

                $inst->paid_amount += $allocate;
                $remainingPayment -= $allocate;
            }
        }

        // 4. Refresh status of every installment
        foreach ($installments as $inst) {
            $inst->refreshStatus();
        }
    }
}
