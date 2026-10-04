<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LedgerController extends Controller
{
    /**
     * Matrix ledger: students on rows, batch months on columns
     * with status-coded cells (Paid / Partial / Due / Overdue).
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Student::class);

        $batch = null;
        if ($request->filled('batch_id')) {
            $batch = Batch::find($request->batch_id);
        }

        if (! $batch) {
            // Default to the newest active batch so the matrix is useful immediately.
            $batch = Batch::whereIn('status', ['running', 'upcoming', 'completed'])
                ->latest('start_date')
                ->first();
        }

        $batches = Batch::orderBy('name')->get();

        if (! $batch) {
            return view('ledger.index', [
                'batches' => $batches,
                'batch' => null,
                'columns' => [],
                'rows' => [],
                'columnTotals' => [],
                'grand' => ['due' => 0, 'paid' => 0],
            ]);
        }

        // ---- Month columns derived from batch duration, anchored at start_date ----
        $columns = [];
        for ($i = 0; $i < max(1, (int) $batch->duration_months); $i++) {
            $start = $batch->start_date->copy()->addMonthsNoOverflow($i);
            $startDate = $start->copy()->startOfMonth();
            $endDate = $start->copy()->endOfMonth();
            $columns[] = [
                'label' => 'M' . ($i + 1),
                'month' => $start->format('Y-m'),
                'name' => $start->format('M y'),
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        }

        $students = Student::with(['installments', 'payments'])
            ->where('batch_id', $batch->id)
            ->orderBy('name')
            ->get();

        $rows = [];
        $columnTotals = array_fill_keys(array_column($columns, 'month'), ['due' => 0, 'paid' => 0]);
        $grand = ['due' => 0, 'paid' => 0];

        foreach ($students as $student) {
            $cells = [];

            foreach ($columns as $col) {
                $insts = $student->installments->filter(fn ($inst) => $inst->due_date
                    && $inst->due_date->copy()->startOfDay()->between($col['start_date']->copy()->startOfDay(), $col['end_date']->copy()->endOfDay()));

                $due = (float) $insts->sum('amount');
                $paid = (float) $insts->sum('paid_amount');
                $status = $this->cellStatus($insts, $due, $paid);

                $cells[$col['month']] = [
                    'status' => $status,
                    'due' => $due,
                    'paid' => $paid,
                    'remaining' => max(0, $due - $paid),
                ];

                $columnTotals[$col['month']]['due'] += $due;
                $columnTotals[$col['month']]['paid'] += $paid;
                $grand['due'] += $due;
                $grand['paid'] += $paid;
            }

            $rows[] = [
                'student' => $student,
                'cells' => $cells,
                'net_fee' => (float) $student->net_fee,
                'total_paid' => $student->total_paid,
                'remaining' => $student->remaining_balance,
                'fee_status' => $student->fee_status,
            ];
        }

        return view('ledger.index', compact(
            'batches',
            'batch',
            'columns',
            'rows',
            'columnTotals',
            'grand'
        ));
    }

    /**
     * Derive the matrix cell status from a set of installments.
     * Paid / Partial / Due / Overdue / none (no installment due this month).
     *
     * @param  \Illuminate\Support\Collection  $insts
     */
    protected function cellStatus($insts, float $due, float $paid): string
    {
        if ($insts->isEmpty() || $due <= 0) {
            return 'none';
        }

        if ($paid >= $due) {
            return 'paid';
        }

        $allPast = $insts->every(fn ($i) => $i->due_date && $i->due_date->lt(now()->startOfDay()));

        if ($paid <= 0) {
            return $allPast ? 'overdue' : 'due';
        }

        // Partially covered: overdue if any covered installment is past due.
        return $allPast ? 'overdue' : 'partial';
    }

    /**
     * Cell drill-down (JSON): installments behind a matrix cell.
     * Powers the "record payment" modal on cell click.
     */
    public function cell(Request $request)
    {
        $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $student = Student::with('batch')->findOrFail($request->student_id);
        $this->authorize('view', $student);
        $start = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $request->month)->endOfMonth();

        $installments = $student->installments()
            ->whereBetween('due_date', [$start, $end])
            ->orderBy('number')
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'number' => $i->number,
                'due_date' => $i->due_date?->format('Y-m-d'),
                'amount' => (float) $i->amount,
                'paid_amount' => (float) $i->paid_amount,
                'remaining' => $i->remaining,
                'status' => $i->status,
            ]);

        return response()->json([
            'student' => ['id' => $student->id, 'name' => $student->name],
            'remaining_balance' => $student->remaining_balance,
            'installments' => $installments,
        ]);
    }
}

