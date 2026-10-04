<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Expense;
use App\Models\Installment;
use App\Models\Payment;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    /**
     * Get all computed stats for the dashboard given a yearMonth (e.g. '2026-10').
     *
     * The returned structure is intentionally composed of scalars and nested
     * arrays only (no Eloquent models / collections) so that it can be safely
     * serialised by the file cache driver and consumed by CLI contexts too.
     */
    public static function getStats(string $yearMonth, bool $useCache = true): array
    {
        $cacheKey = "dashboard_stats_{$yearMonth}";

        $fetcher = function () use ($yearMonth) {
            $monthStart = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();
            $monthEnd = Carbon::createFromFormat('Y-m', $yearMonth)->endOfMonth();

            // 1. Expected for this month = installments due in this month
            $installmentsDueInMonth = Installment::dueInMonth($yearMonth)->get();
            $expectedAmount = (float) $installmentsDueInMonth->sum('amount');
            $receivedAgainstMonthInstallments = (float) $installmentsDueInMonth->sum('paid_amount');

            // 2. Received total in this month by paid_on
            $receivedAmount = (float) Payment::whereBetween('paid_on', [$monthStart, $monthEnd])->sum('amount');

            // 3. Pending for month installments
            $pendingThisMonth = max(0, $expectedAmount - $receivedAgainstMonthInstallments);

            // 4. Overdue carried over — use DB aggregate instead of loading all rows
            $carriedOverdue = (float) DB::table('installments')
                ->where('due_date', '<', $monthStart)
                ->whereColumn('paid_amount', '<', 'amount')
                ->selectRaw('SUM(amount - paid_amount) as total')
                ->value('total') ?? 0;

            // 5. Collection rate (total receipts in month vs expected)
            $collectionRate = $expectedAmount > 0
                ? round(($receivedAmount / $expectedAmount) * 100, 1)
                : ($receivedAmount > 0 ? 100 : 0);

            // 6. Counts & Active stats
            $activeStudentsCount = Student::active()->count();
            $runningBatchesCount = Batch::running()->count();
            $newJoinsThisMonth = Student::whereBetween('join_date', [$monthStart, $monthEnd])->count();

            // 7. Defaulters
            $defaulterStudents = Student::with(['batch', 'installments'])
                ->active()
                ->whereHas('installments', function ($q) {
                    $q->overdue();
                })
                ->get()
                ->sortByDesc('overdue_amount');

            $defaultersCount = $defaulterStudents->count();
            $totalOverdueAmount = (float) $defaulterStudents->sum('overdue_amount');

            // 8. Upcoming dues in next 7 days
            $next7DaysStart = now()->toDateString();
            $next7DaysEnd = now()->addDays(7)->toDateString();
            $upcomingDues = Installment::with(['student.batch'])
                ->whereBetween('due_date', [$next7DaysStart, $next7DaysEnd])
                ->whereColumn('paid_amount', '<', 'amount')
                ->orderBy('due_date')
                ->take(6)
                ->get();

            // 9. Payment Methods Breakdown — use DB aggregate for the selected month
            $methodAmounts = Payment::whereBetween('paid_on', [$monthStart, $monthEnd])
                ->selectRaw('method, SUM(amount) as total')
                ->groupBy('method')
                ->pluck('total', 'method')
                ->all();

            $methodCounts = [
                'cash' => (float) ($methodAmounts['cash'] ?? 0),
                'jazzcash' => (float) ($methodAmounts['jazzcash'] ?? 0),
                'easypaisa' => (float) ($methodAmounts['easypaisa'] ?? 0),
                'bank' => (float) ($methodAmounts['bank'] ?? 0),
                'other' => (float) ($methodAmounts['other'] ?? 0),
            ];

            // 10. 12-Month Revenue Trend — 2 grouped queries instead of 24 separate ones
            $trendStart = Carbon::createFromFormat('Y-m', $yearMonth)->subMonths(11)->startOfMonth();
            $trendEnd = Carbon::createFromFormat('Y-m', $yearMonth)->endOfMonth();

            $driver = DB::connection()->getDriverName();
            $dueYm = $driver === 'sqlite' ? "strftime('%Y-%m', due_date)" : "DATE_FORMAT(due_date, '%Y-%m')";
            $paidYm = $driver === 'sqlite' ? "strftime('%Y-%m', paid_on)" : "DATE_FORMAT(paid_on, '%Y-%m')";

            $expectedByMonth = DB::table('installments')
                ->whereBetween('due_date', [$trendStart, $trendEnd])
                ->selectRaw("{$dueYm} as ym, SUM(amount) as total")
                ->groupByRaw($dueYm)
                ->pluck('total', 'ym')
                ->all();

            $receivedByMonth = DB::table('payments')
                ->whereNull('deleted_at')
                ->whereBetween('paid_on', [$trendStart, $trendEnd])
                ->selectRaw("{$paidYm} as ym, SUM(amount) as total")
                ->groupByRaw($paidYm)
                ->pluck('total', 'ym')
                ->all();

            $trendLabels = [];
            $trendExpected = [];
            $trendReceived = [];

            for ($i = 11; $i >= 0; $i--) {
                $target = Carbon::createFromFormat('Y-m', $yearMonth)->subMonths($i);
                $ym = $target->format('Y-m');

                $trendLabels[] = $target->format('M Y');
                $trendExpected[] = (float) ($expectedByMonth[$ym] ?? 0);
                $trendReceived[] = (float) ($receivedByMonth[$ym] ?? 0);
            }

            // 11. Batch-wise collection progress (plain arrays for safe caching)
            $batches = Batch::with(['students.installments', 'students.payments'])
                ->whereIn('status', ['running', 'upcoming'])
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($batch) {
                    $exp = (float) $batch->total_expected;
                    $col = (float) $batch->total_collected;
                    $pct = $exp > 0 ? min(100, round(($col / $exp) * 100, 1)) : 0;

                    return [
                        'id' => $batch->id,
                        'name' => $batch->name,
                        'code' => $batch->code,
                        'expected' => $exp,
                        'collected' => $col,
                        'remaining' => max(0, $exp - $col),
                        'percentage' => $pct,
                        'students_count' => $batch->students->count(),
                    ];
                })
                ->values()
                ->all();

            // 12. Recent payments feed (plain arrays)
            $recentPayments = Payment::with(['student.batch', 'receiver'])
                ->latest('paid_on')
                ->latest('id')
                ->take(6)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'student_id' => $p->student_id,
                    'student_name' => $p->student?->name ?? 'Unknown',
                    'batch_code' => $p->student?->batch?->code,
                    'receipt_no' => $p->receipt_no,
                    'paid_on' => $p->paid_on?->format('Y-m-d'),
                    'paid_on_label' => $p->paid_on?->format('d M'),
                    'amount' => (float) $p->amount,
                    'method' => $p->method,
                ])
                ->values()
                ->all();

            // 13. Expenses in month
            $expensesThisMonth = (float) Expense::whereBetween('spent_on', [$monthStart, $monthEnd])->sum('amount');
            $netProfit = $receivedAmount - $expensesThisMonth;

            // 14. Upcoming dues (plain arrays)
            $upcomingDuesArray = $upcomingDues->map(function ($inst) {
                $dueDate = $inst->due_date;

                return [
                    'id' => $inst->id,
                    'student_id' => $inst->student_id,
                    'student_name' => $inst->student?->name ?? 'Unknown',
                    'batch_code' => $inst->student?->batch?->code,
                    'number' => (int) $inst->number,
                    'due_date' => $dueDate?->format('Y-m-d'),
                    'due_date_label' => $dueDate?->format('d M'),
                    'is_today' => (bool) $dueDate?->isToday(),
                    'days_left' => $dueDate
                        ? max(0, (int) now()->startOfDay()->diffInDays($dueDate->copy()->startOfDay(), false))
                        : 0,
                    'amount' => (float) $inst->amount,
                    'remaining' => (float) $inst->remaining,
                    'whatsapp' => $inst->student ? $inst->student->getWhatsAppUrl() : '#',
                ];
            })->values()->all();

            // 15. Top defaulters (plain arrays)
            $topDefaulters = $defaulterStudents->take(5)->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'batch_code' => $d->batch?->code,
                'overdue_amount' => (float) $d->overdue_amount,
                'days_overdue' => (int) $d->days_overdue,
                'whatsapp' => $d->getWhatsAppUrl(),
            ])->values()->all();

            return [
                'month' => $yearMonth,
                'month_name' => $monthStart->format('F Y'),
                'expected' => $expectedAmount,
                'received' => $receivedAmount,
                'pending' => $pendingThisMonth,
                'carried_overdue' => $carriedOverdue,
                'total_overdue' => $totalOverdueAmount,
                'defaulters_count' => $defaultersCount,
                'collection_rate' => $collectionRate,
                'active_students' => $activeStudentsCount,
                'running_batches' => $runningBatchesCount,
                'new_joins' => $newJoinsThisMonth,
                'expenses' => $expensesThisMonth,
                'net_profit' => $netProfit,
                'upcoming_dues' => $upcomingDuesArray,
                'upcoming_dues_count' => count($upcomingDuesArray),
                'top_defaulters' => $topDefaulters,
                'method_breakdown' => $methodCounts,
                'batches_progress' => $batches,
                'recent_payments' => $recentPayments,
                'expenses_breakdown' => Expense::whereBetween('spent_on', [$monthStart, $monthEnd])
                    ->selectRaw('category, SUM(amount) as total')
                    ->groupBy('category')
                    ->pluck('total', 'category')
                    ->map(fn ($v) => (float) $v)
                    ->all(),
                'trend' => [
                    'labels' => $trendLabels,
                    'expected' => $trendExpected,
                    'received' => $trendReceived,
                ],
            ];
        };

        // Cache for 5 minutes instead of 60 seconds — this is an expensive computation
        if ($useCache) {
            return Cache::remember($cacheKey, 300, $fetcher);
        }

        return $fetcher();
    }
}
