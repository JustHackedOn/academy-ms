<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    /**
     * Read a stored academy setting (cached by the Setting model).
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('currency')) {
    /**
     * Currency symbol/code shown next to every amount (Settings > Finance).
     */
    function currency(): string
    {
        static $symbol = null;

        if ($symbol === null) {
            $symbol = trim((string) (Setting::get('currency') ?: 'Rs'));
        }

        return $symbol !== '' ? $symbol : 'Rs';
    }
}

if (! function_exists('academy_name')) {
    /**
     * Canonical academy name used on receipts, reports, sidebar, titles and WhatsApp.
     */
    function academy_name(): string
    {
        static $name = null;

        if ($name === null) {
            $name = trim((string) Setting::get('academy_name'));
        }

        return $name !== '' ? $name : 'Academy FMS';
    }
}

if (! function_exists('academy_initials')) {
    /**
     * Two-letter monogram for the simple logo mark.
     */
    function academy_initials(): string
    {
        static $initials = null;

        if ($initials === null) {
            $initials = '';
            foreach (array_slice(preg_split('/\s+/', academy_name()), 0, 2) as $word) {
                if ($word !== '') {
                    $initials .= mb_strtoupper(mb_substr($word, 0, 1));
                }
            }
        }

        return $initials !== '' ? $initials : 'AF';
    }
}

if (! function_exists('escape_like')) {
    /**
     * Escape SQL LIKE wildcards (% and _) in user input to prevent wildcard injection.
     */
    function escape_like(string $value): string
    {
        return str_replace(
            ['%', '_', '\\'],
            ['\\%', '\\_', '\\\\'],
            $value
        );
    }
}

if (! function_exists('money')) {
    /**
     * Format an amount with the configured currency: "PKR 12,500".
     */
    function money(int|float|string|null $amount, int $decimals = 0): string
    {
        return currency() . ' ' . number_format((float) $amount, $decimals, '.', ',');
    }
}

if (! function_exists('pdf_image_base64')) {
    /**
     * Compress and encode an image to a base64 Data URI for DomPDF to prevent glitches and memory bloat.
     */
    function pdf_image_base64(?string $path, int $maxWidth = 300, int $maxHeight = 300, int $quality = 80): ?string
    {
        if (empty($path)) {
            return null;
        }

        try {
            $fullPath = null;
            if (file_exists($path)) {
                $fullPath = $path;
            } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
            } elseif (file_exists(public_path($path))) {
                $fullPath = public_path($path);
            }

            if (! $fullPath || ! file_exists($fullPath) || ! is_readable($fullPath)) {
                return null;
            }

            $imageInfo = @getimagesize($fullPath);
            if (! $imageInfo) {
                return null;
            }

            [$width, $height, $type] = $imageInfo;
            $mime = $imageInfo['mime'] ?? 'image/jpeg';

            // If GD is not loaded, just read the raw file and encode
            if (! extension_loaded('gd')) {
                $data = file_get_contents($fullPath);
                return 'data:' . $mime . ';base64,' . base64_encode($data);
            }

            // Create image resource based on type
            $source = null;
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $source = @imagecreatefromjpeg($fullPath);
                    break;
                case IMAGETYPE_PNG:
                    $source = @imagecreatefrompng($fullPath);
                    break;
                case IMAGETYPE_WEBP:
                    $source = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($fullPath) : null;
                    break;
                case IMAGETYPE_GIF:
                    $source = @imagecreatefromgif($fullPath);
                    break;
            }

            if (! $source) {
                $data = file_get_contents($fullPath);
                return 'data:' . $mime . ';base64,' . base64_encode($data);
            }

            // Calculate resized dimensions
            $ratio = min($maxWidth / max(1, $width), $maxHeight / max(1, $height), 1.0);
            $newWidth = (int) round($width * $ratio);
            $newHeight = (int) round($height * $ratio);

            $target = imagecreatetruecolor($newWidth, $newHeight);

            // Handle transparency for PNG and WEBP
            if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
                imagealphablending($target, false);
                imagesavealpha($target, true);
                $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
                imagefilledrectangle($target, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            ob_start();
            if ($type === IMAGETYPE_PNG) {
                imagepng($target, null, 7);
                $outputMime = 'image/png';
            } else {
                imagejpeg($target, null, $quality);
                $outputMime = 'image/jpeg';
            }
            $imageData = ob_get_clean();

            imagedestroy($source);
            imagedestroy($target);

            return 'data:' . $outputMime . ';base64,' . base64_encode($imageData);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
