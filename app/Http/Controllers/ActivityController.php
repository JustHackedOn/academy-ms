<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    /**
     * Chronological audit trail of everything that changed in the system.
     */
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Only administrators can view the activity log.');

        $query = ActivityLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        if ($request->filled('search')) {
            $search = escape_like($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('ip', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        $logs = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(25)->withQueryString();

        $actions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $subjectTypes = ActivityLog::query()
            ->whereNotNull('subject_type')
            ->select('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->mapWithKeys(fn ($t) => [$t => class_basename($t)]);

        $stats = [
            'total' => ActivityLog::count(),
            'today' => ActivityLog::whereDate('created_at', today())->count(),
            'week' => ActivityLog::where('created_at', '>=', now()->subDays(7))->count(),
            'actors' => ActivityLog::whereNotNull('user_id')->distinct('user_id')->count('user_id'),
        ];

        return view('activity.index', [
            'logs' => $logs,
            'actions' => $actions,
            'subjectTypes' => $subjectTypes,
            'users' => User::orderBy('name')->get(),
            'stats' => $stats,
        ]);
    }
}
