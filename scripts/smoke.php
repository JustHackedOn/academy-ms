<?php

// Smoke-test helper: exercised via `php artisan tinker --execute` or directly.
// Usage: php scripts/smoke.php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Student;
use App\Services\PaymentAllocationService;
use Illuminate\Support\Facades\Auth;

Auth::loginUsingId(1);

$students = Student::where('status', 'active')
    ->with('installments')
    ->get()
    ->filter(fn ($s) => $s->remaining_balance > 0)
    ->take(3);

foreach ($students as $s) {
    echo "Student: {$s->name} | balance: Rs " . number_format($s->remaining_balance, 0) . PHP_EOL;
}

$target = $students->first();
if (! $target) {
    echo "No student with balance. Nothing to test.\n";
    exit(0);
}

$before = $target->remaining_balance;
$amount = min(5000, $before);

echo "Recording Rs {$amount} for {$target->name}...\n";

try {
    $payment = PaymentAllocationService::recordPayment(
        student: $target,
        amount: $amount,
        paidOn: now()->format('Y-m-d'),
        method: 'cash',
        reference: 'SMOKE-TEST',
        note: 'Smoke test payment',
    );

    echo "Receipt: {$payment->receipt_no}\n";
    echo "Allocations: " . $payment->allocations()->count() . PHP_EOL;
    $target->refresh();
    echo "Balance before: Rs " . number_format($before, 0) . " → after: Rs " . number_format($target->remaining_balance, 0) . PHP_EOL;

    // Overpayment guard test
    try {
        PaymentAllocationService::recordPayment(
            student: $target,
            amount: $target->remaining_balance + 1000,
            paidOn: now()->format('Y-m-d'),
            method: 'cash',
        );
        echo "FAIL: overpayment was NOT blocked\n";
    } catch (\Illuminate\Validation\ValidationException $e) {
        echo "PASS: overpayment blocked → " . collect($e->errors())->flatten()->first() . PHP_EOL;
    }

    // Cleanup test payment
    $payment->delete();
    PaymentAllocationService::recalculateStudentInstallments($target->fresh());
    echo "Cleanup done. Balance restored to Rs " . number_format($target->fresh()->remaining_balance, 0) . PHP_EOL;
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}

echo "SMOKE OK\n";

// --- PDF receipt generation check ---
$latest = \App\Models\Payment::orderBy('id', 'desc')->first();
if ($latest) {
    $latest->load(['student.batch', 'receiver', 'allocations.installment']);
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payments.pdf.receipt', ['payment' => $latest])
        ->setPaper('a4', 'portrait');
    $bytes = strlen($pdf->output());
    echo "PDF receipt {$latest->receipt_no}: {$bytes} bytes\n";
    echo ($bytes > 2000 ? "PASS" : "FAIL") . ": PDF generated\n";
}

