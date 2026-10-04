<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Expense;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Everything the monthly report page / PDF needs for one month.
     */
    public static function monthlySummary(string $yearMonth): array
    {
        $start = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $yearMonth)->endOfMonth();

        $installments = Installment::with(['student.batch'])
            ->whereBetween('due_date', [$start, $end])
            ->get();

        $expected = (float) $installments->sum('amount');
        $collectedAgainst = (float) $installments->sum('paid_amount');

        $payments = Payment::with(['student.batch', 'receiver'])
            ->whereBetween('paid_on', [$start, $end])
            ->orderBy('paid_on')
            ->get();

        $received = (float) $payments->sum('amount');
        $expenses = (float) Expense::whereBetween('spent_on', [$start, $end])->sum('amount');

        $methodBreakdown = collect(['cash', 'jazzcash', 'easypaisa', 'bank', 'other'])
            ->mapWithKeys(fn ($m) => [$m => (float) $payments->where('method', $m)->sum('amount')])
            ->all();

        $overdueCarried = (float) Installment::where('due_date', '<', $start)
            ->whereColumn('paid_amount', '<', 'amount')
            ->get()
            ->sum(fn ($i) => (float) $i->amount - (float) $i->paid_amount);

        $batchRows = Batch::with(['students'])
            ->orderBy('name')
            ->get()
            ->map(function (Batch $batch) use ($start, $end) {
                $ids = $batch->students->pluck('id');

                $bExpected = (float) Installment::whereIn('student_id', $ids)
                    ->whereBetween('due_date', [$start, $end])->sum('amount');
                $bCollected = (float) Installment::whereIn('student_id', $ids)
                    ->whereBetween('due_date', [$start, $end])->sum('paid_amount');
                $bReceived = (float) Payment::whereIn('student_id', $ids)
                    ->whereBetween('paid_on', [$start, $end])->sum('amount');

                return [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'code' => $batch->code,
                    'students' => $ids->count(),
                    'expected' => $bExpected,
                    'collected' => $bCollected,
                    'received' => $bReceived,
                    'remaining' => max(0, $bExpected - $bCollected),
                    'rate' => $bExpected > 0 ? round(($bCollected / $bExpected) * 100, 1) : 0.0,
                ];
            })
            ->filter(fn ($row) => $row['students'] > 0)
            ->values()
            ->all();

        return [
            'month' => $yearMonth,
            'month_name' => $start->format('F Y'),
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'expected' => $expected,
            'collected_against' => $collectedAgainst,
            'received' => $received,
            'pending' => max(0, $expected - $collectedAgainst),
            'overdue_carried' => $overdueCarried,
            'expenses' => $expenses,
            'net_profit' => $received - $expenses,
            'collection_rate' => $expected > 0 ? round(($collectedAgainst / $expected) * 100, 1) : ($received > 0 ? 100.0 : 0.0),
            'payments_count' => $payments->count(),
            'students_with_dues' => $installments
                ->filter(fn ($i) => (float) $i->paid_amount < (float) $i->amount)
                ->pluck('student_id')
                ->unique()
                ->count(),
            'method_breakdown' => $methodBreakdown,
            'batch_rows' => $batchRows,
            'payments' => $payments,
        ];
    }

    /**
     * Batch-wise performance summary (all batches or a single one).
     */
    public static function batchSummary(?int $batchId = null): array
    {
        $batches = Batch::query()
            ->when($batchId, fn ($q) => $q->where('id', $batchId))
            ->withCount('students')
            ->with(['students' => fn ($q) => $q->with('payments')])
            ->orderBy('name')
            ->get();

        $rows = $batches->map(function (Batch $batch) {
            $expected = (float) $batch->total_expected;
            $collected = (float) $batch->total_collected;
            $overdue = (float) Installment::whereHas('student', fn ($q) => $q->where('batch_id', $batch->id))
                ->whereColumn('paid_amount', '<', 'amount')
                ->where('due_date', '<', now()->toDateString())
                ->selectRaw('COALESCE(SUM(amount - paid_amount), 0) as overdue')
                ->value('overdue');

            return [
                'id' => $batch->id,
                'name' => $batch->name,
                'code' => $batch->code,
                'status' => $batch->status,
                'start_date' => $batch->start_date?->format('d M Y'),
                'end_date' => $batch->end_date?->format('d M Y'),
                'students' => $batch->students_count,
                'expected' => $expected,
                'collected' => $collected,
                'remaining' => max(0, $expected - $collected),
                'overdue' => $overdue,
                'rate' => $expected > 0 ? round(($collected / $expected) * 100, 1) : 0.0,
            ];
        })->values()->all();

        return [
            'rows' => $rows,
            'totals' => [
                'students' => array_sum(array_column($rows, 'students')),
                'expected' => array_sum(array_column($rows, 'expected')),
                'collected' => array_sum(array_column($rows, 'collected')),
                'remaining' => array_sum(array_column($rows, 'remaining')),
                'overdue' => array_sum(array_column($rows, 'overdue')),
            ],
        ];
    }

    /**
     * Filterable payments query shared by the report table and CSV export.
     */
    public static function paymentsQuery(array $filters): Builder
    {
        return Payment::with(['student.batch', 'receiver'])
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->where('paid_on', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->where('paid_on', '<=', $v))
            ->when($filters['method'] ?? null, fn ($q, $v) => $q->where('method', $v))
            ->when($filters['batch_id'] ?? null, fn ($q, $v) => $q->whereHas('student', fn ($sq) => $sq->where('batch_id', $v)))
            ->when($filters['student_id'] ?? null, fn ($q, $v) => $q->where('student_id', $v))
            ->orderBy('paid_on')
            ->orderBy('id');
    }

    /**
     * Filterable students query used by the student/dues export.
     */
    public static function studentsQuery(array $filters): Builder
    {
        return Student::with(['batch', 'installments'])
            ->when($filters['batch_id'] ?? null, fn ($q, $v) => $q->where('batch_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when(($filters['overdue'] ?? null) === '1', fn ($q) => $q->whereHas('installments', fn ($sq) => $sq->overdue()))
            ->orderBy('name');
    }

    /**
     * Build a CSV string (with UTF-8 BOM so Excel renders Urdu/accented text).
     */
    public static function toCsv(array $headings, iterable $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, $headings);

        foreach ($rows as $row) {
            fputcsv($handle, array_map(
                fn ($value) => is_float($value) ? number_format($value, 2, '.', '') : (string) $value,
                (array) $row
            ));
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return "\xEF\xBB\xBF" . $csv;
    }

    /**
     * Student export rows (flattened for spreadsheets).
     */
    public static function studentRows(Collection $students): array
    {
        return $students->map(fn (Student $s) => [
            'Name' => $s->name,
            'Phone' => PhoneFormatter::display($s->phone),
            'Batch' => $s->batch?->code,
            'Status' => $s->status,
            'Fee Type' => $s->fee_type,
            'Net Fee' => (float) $s->net_fee,
            'Paid' => $s->total_paid,
            'Balance' => $s->remaining_balance,
            'Overdue' => $s->overdue_amount,
            'Days Overdue' => $s->days_overdue,
            'Fee Status' => $s->fee_status,
            'Join Date' => $s->join_date?->format('Y-m-d'),
        ])->all();
    }

    /**
     * Payment export rows (flattened for spreadsheets).
     */
    public static function paymentRows(Collection $payments): array
    {
        return $payments->map(fn (Payment $p) => [
            'Receipt No' => $p->receipt_no,
            'Date' => $p->paid_on?->format('Y-m-d'),
            'Student' => $p->student?->name,
            'Batch' => $p->student?->batch?->code,
            'Amount' => (float) $p->amount,
            'Method' => $p->method,
            'Reference' => $p->reference,
            'Note' => $p->note,
            'Received By' => $p->receiver?->name,
        ])->all();
    }
}
