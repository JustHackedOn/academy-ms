<?php

namespace App\Services;

use Carbon\Carbon;

class MonthResolver
{
    /**
     * Normalise an arbitrary ?month= value into a safe 'Y-m' key,
     * falling back to the current month when invalid or out of range.
     */
    public static function normalize(?string $month): string
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

        // Carbon happily rolls over 2026-13 into 2027-01, so verify the round-trip.
        if ($parsed->format('Y-m') !== $month) {
            return $current;
        }

        if ($parsed->year < 2000 || $parsed->year > (int) now()->year + 5) {
            return $current;
        }

        return $parsed->format('Y-m');
    }
}
