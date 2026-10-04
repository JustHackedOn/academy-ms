<?php

namespace App\Services;

class PhoneFormatter
{
    /**
     * Normalize a Pakistani phone number to standard format: 92XXXXXXXXXX (digits only).
     */
    public static function normalize(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        // Strip everything except digits
        $digits = preg_replace('/[^\d]/', '', $phone);

        // If starts with 03 (e.g. 03001234567) -> replace leading 0 with 92
        if (str_starts_with($digits, '03') && strlen($digits) === 11) {
            return '92' . substr($digits, 1);
        }

        // If starts with 3 (e.g. 3001234567) -> prepend 92
        if (str_starts_with($digits, '3') && strlen($digits) === 10) {
            return '92' . $digits;
        }

        // If starts with 92 (e.g. 923001234567)
        if (str_starts_with($digits, '92') && strlen($digits) === 12) {
            return $digits;
        }

        // If starts with 0092
        if (str_starts_with($digits, '0092')) {
            return substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Format for beautiful UI presentation, e.g. 0300-1234567
     */
    public static function display(?string $phone): string
    {
        $normalized = self::normalize($phone);
        if (strlen($normalized) === 12 && str_starts_with($normalized, '923')) {
            // 92 300 1234567 -> 0300-1234567
            return '0' . substr($normalized, 2, 3) . '-' . substr($normalized, 5);
        }

        return $phone ?? '';
    }
}
