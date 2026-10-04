<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\ActivityLog;
use App\Models\Batch;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Student;
use App\Services\PaymentAllocationService;
use App\Services\PhoneFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Global payment history with filters (date range, method, batch, student).
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $query = Payment::with(['student.batch', 'receiver', 'allocations.installment']);

        if ($request->filled('from')) {
            $query->where('paid_on', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('paid_on', '<=', $request->to);
        }

        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        if ($request->filled('batch_id')) {
            $query->whereHas('student', fn ($q) => $q->where('batch_id', $request->batch_id));
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('search')) {
            $search = escape_like($request->search);
            $digits = preg_replace('/[^\d]/', '', $request->search);

            $query->where(function ($q) use ($search, $digits) {
                $q->where('receipt_no', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($sq) use ($search, $digits) {
                        $sq->where('name', 'like', "%{$search}%");
                        if (! empty($digits)) {
                            $sq->orWhere('phone', 'like', "%{$digits}%");
                        }
                    });
            });
        }

        $payments = $query->latest('paid_on')->latest('id')->paginate(20)->withQueryString();

        // KPI tiles for the active filter window
        $windowStart = $request->filled('from') ? Carbon::parse($request->from) : now()->startOfMonth();
        $windowEnd = $request->filled('to') ? Carbon::parse($request->to) : now()->endOfMonth();

        $stats = [
            'total' => (float) Payment::whereBetween('paid_on', [$windowStart, $windowEnd])->sum('amount'),
            'count' => (int) Payment::whereBetween('paid_on', [$windowStart, $windowEnd])->count(),
            'today' => (float) Payment::whereDate('paid_on', now()->toDateString())->sum('amount'),
            'all_time' => (float) Payment::sum('amount'),
        ];

        $batches = Batch::orderBy('name')->get();

        return view('payments.index', compact('payments', 'stats', 'batches'));
    }

    /**
     * Global "Record Payment" screen.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Payment::class);

        $students = Student::with('batch')
            ->active()
            ->orderBy('name')
            ->get();

        $selectedStudent = null;
        if ($request->filled('student_id')) {
            $selectedStudent = $students->firstWhere('id', (int) $request->student_id)
                ?? Student::with('batch')->find($request->student_id);
        }

        return view('payments.create', compact('students', 'selectedStudent'));
    }

    /**
     * Record a payment. Allocation, overpayment guard and receipt number
     * generation are handled inside PaymentAllocationService.
     */
    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $data = $request->validated();

        $student = Student::with('batch')->findOrFail($data['student_id']);

        if ($student->remaining_balance <= 0) {
            throw ValidationException::withMessages([
                'amount' => "This student's fee is already fully cleared. No payment can be recorded.",
            ]);
        }

        try {
            $payment = PaymentAllocationService::recordPayment(
                student: $student,
                amount: (float) $data['amount'],
                paidOn: $data['paid_on'],
                method: $data['method'],
                forMonth: $data['for_month'] ?? null,
                reference: $data['reference'] ?? null,
                note: $data['note'] ?? null,
                specificInstallmentId: ! empty($data['installment_id']) ? (int) $data['installment_id'] : null,
            );
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('payments.receipt', $payment)
            ->with('success', "Payment recorded. Receipt {$payment->receipt_no} generated.");
    }

    /**
     * Edit an existing payment (amount/date/method), then rebuild allocations.
     */
    public function edit(Payment $payment): View
    {
        $this->authorize('update', $payment);

        $payment->load(['student.batch', 'allocations.installment']);

        $students = Student::with('batch')->orderBy('name')->get();

        return view('payments.edit', compact('payment', 'students'));
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->authorize('update', $payment);

        $data = $request->validated();

        $student = $payment->student;
        $otherPayments = (float) $student->payments()->where('id', '!=', $payment->id)->sum('amount');
        $newTotal = $otherPayments + (float) $data['amount'];

        if (round($newTotal, 2) > round((float) $student->net_fee, 2)) {
            throw ValidationException::withMessages([
                'amount' => 'Overpayment blocked! Total payments would be ' . money($newTotal, 2)
                    . ' but the net fee is ' . money((float) $student->net_fee, 2) . '.',
            ]);
        }

        $old = $payment->toArray();

        DB::transaction(function () use ($payment, $data, $student) {
            $paidDate = Carbon::parse($data['paid_on']);

            $payment->update([
                'amount' => $data['amount'],
                'paid_on' => $paidDate->format('Y-m-d'),
                'for_month' => $data['for_month'] ?? $paidDate->format('Y-m'),
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            // Rebuild every allocation for this student from scratch.
            PaymentAllocationService::recalculateStudentInstallments($student);
        });

        ActivityLog::log('updated_payment', $payment, $old, $payment->fresh()->toArray());

        return redirect()
            ->route('payments.index')
            ->with('success', "Payment {$payment->receipt_no} updated and installment allocations rebuilt.");
    }

    /**
     * Soft-delete a payment (never hard-delete financial data) + recalc.
     */
    public function destroy(Payment $payment): RedirectResponse
    {
        $this->authorize('delete', $payment);

        $old = $payment->toArray();
        $student = $payment->student;

        DB::transaction(function () use ($payment, $student) {
            $payment->delete();
            PaymentAllocationService::recalculateStudentInstallments($student);
        });

        ActivityLog::log('deleted_payment', $payment, $old, null);

        return redirect()
            ->route('payments.index')
            ->with('success', 'Payment ' . $payment->receipt_no . ' deleted and installments recalculated.');
    }

    /**
     * On-screen (printable) receipt.
     */
    public function receipt(Payment $payment): View
    {
        $this->authorize('view', $payment);

        $payment->load(['student.batch', 'receiver', 'allocations.installment']);

        return view('payments.receipt', compact('payment'));
    }

    /**
     * Downloadable PDF receipt via dompdf.
     *
     * @return \Illuminate\Http\Response
     */
    public function receiptPdf(Payment $payment)
    {
        $this->authorize('view', $payment);

        $payment->load(['student.batch', 'receiver', 'allocations.installment']);

        $pdf = Pdf::loadView('payments.pdf.receipt', compact('payment'))
            ->setPaper('a4', 'portrait');

        return $pdf->download($payment->receipt_no . '.pdf');
    }

    /**
     * Click-to-share receipt on WhatsApp (prefilled summary).
     */
    public function whatsappReceipt(Payment $payment): RedirectResponse
    {
        $this->authorize('view', $payment);

        $payment->load(['student.batch']);

        $student = $payment->student;
        $phone = PhoneFormatter::normalize($student->phone);

        if (empty($phone)) {
            return back()->with('error', 'This student has no WhatsApp number on file.');
        }

        $academy = academy_name();

        $message = "Receipt {$payment->receipt_no}\n"
            . "{$academy}\n"
            . "Student: {$student->name}\n"
            . 'Batch: ' . ($student->batch?->name ?? '-') . "\n"
            . 'Amount: ' . money($payment->amount, 0) . "\n"
            . 'Method: ' . ucfirst($payment->method) . "\n"
            . 'Date: ' . $payment->paid_on?->format('d M Y') . "\n"
            . 'Remaining balance: ' . money($student->remaining_balance, 0) . "\n"
            . "\nThank you for your payment!";

        return redirect()->away(
            "https://wa.me/{$phone}?text=" . rawurlencode($message)
        );
    }

    /**
     * JSON fee snapshot used by payment screens (live balance + installments).
     */
    public function studentSnapshot(Request $request)
    {
        $request->validate(['student_id' => ['required', 'exists:students,id']]);

        $student = Student::with(['batch', 'installments'])->findOrFail($request->student_id);

        return response()->json([
            'id' => $student->id,
            'name' => $student->name,
            'batch' => $student->batch?->name,
            'net_fee' => (float) $student->net_fee,
            'total_paid' => $student->total_paid,
            'remaining_balance' => $student->remaining_balance,
            'installments' => $student->installments
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'number' => $i->number,
                    'due_date' => $i->due_date?->format('Y-m-d'),
                    'amount' => (float) $i->amount,
                    'paid_amount' => (float) $i->paid_amount,
                    'remaining' => $i->remaining,
                    'status' => $i->status,
                ])
                ->values(),
        ]);
    }
}


