<?php

namespace App\Http\Controllers;

use App\Services\DashboardStatsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Bento analytics dashboard for a selected month (defaults to current).
     */
    public function index(Request $request): View
    {
        $month = $this->resolveMonth($request->query('month'));

        $stats = DashboardStatsService::getStats($month);

        return view('dashboard', $stats + ['selected_month' => $month]);
    }

    /**
     * Normalise an arbitrary ?month= value into a safe 'Y-m' key,
     * falling back to the current month when invalid or out of range.
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

        // Guard against absurd ranges (e.g. year 0000 or far future) and
        // month overflow like 2026-13 which Carbon happily rolls over.
        if ($parsed->format('Y-m') !== $month) {
            return $current;
        }

        if ($parsed->year < 2000 || $parsed->year > (int) now()->year + 5) {
            return $current;
        }

        return $parsed->format('Y-m');
    }
}
