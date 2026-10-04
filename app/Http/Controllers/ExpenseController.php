<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\ActivityLog;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public const CATEGORIES = [
        'Utilities', 'Rent', 'Salaries', 'Marketing', 'Software', 'Equipment', 'Miscellaneous',
    ];

    /**
     * Expenses ledger with month / category filters and net-profit summary.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Expense::class);

        $month = $this->resolveMonth($request->query('month'));

        $query = Expense::with('user');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('from')) {
            $query->where('spent_on', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('spent_on', '<=', $request->to);
        }

        if ($request->filled('search')) {
            $search = escape_like($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%");
            });
        }

        $expenses = $query->latest('spent_on')->latest('id')->paginate(15)->withQueryString();

        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        $monthTotal = (float) Expense::whereBetween('spent_on', [$monthStart, $monthEnd])->sum('amount');
        $allTimeTotal = (float) Expense::sum('amount');

        $byCategory = Expense::whereBetween('spent_on', [$monthStart, $monthEnd])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->map(fn ($v) => (float) $v)
            ->all();

        $receivedThisMonth = (float) \App\Models\Payment::whereBetween('paid_on', [$monthStart, $monthEnd])->sum('amount');

        $stats = [
            'month' => $month,
            'month_name' => $monthStart->format('F Y'),
            'month_total' => $monthTotal,
            'all_time_total' => $allTimeTotal,
            'received' => $receivedThisMonth,
            'net_profit' => $receivedThisMonth - $monthTotal,
            'count' => Expense::count(),
        ];

        return view('expenses.index', [
            'expenses' => $expenses,
            'stats' => $stats,
            'byCategory' => $byCategory,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $this->authorize('create', Expense::class);

        $expense = Expense::create($request->validated() + ['user_id' => auth()->id()]);

        ActivityLog::log('created_expense', $expense, null, $expense->toArray());

        return redirect()->route('expenses.index')
            ->with('success', "Expense '{$expense->title}' recorded.");
    }

    public function update(ExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);

        $old = $expense->toArray();
        $expense->update($request->validated());

        ActivityLog::log('updated_expense', $expense, $old, $expense->fresh()->toArray());

        return redirect()->route('expenses.index')
            ->with('success', "Expense '{$expense->title}' updated.");
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        $old = $expense->toArray();
        $title = $expense->title;
        $expense->delete();

        ActivityLog::log('deleted_expense', $expense, $old, null);

        return redirect()->route('expenses.index')
            ->with('success', "Expense '{$title}' deleted.");
    }

    /**
     * Normalise ?month= into a safe 'Y-m' key.
     */
    protected function resolveMonth(?string $month): string
    {
        $current = now()->format('Y-m');

        if (! $month || ! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $current;
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Throwable) {
            return $current;
        }

        if ($parsed->format('Y-m') !== $month || $parsed->year < 2000 || $parsed->year > (int) now()->year + 5) {
            return $current;
        }

        return $parsed->format('Y-m');
    }
}
