<?php

// Phase 7 smoke test: Bento analytics dashboard renders with all data wired.
// Usage: C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe scripts/phase7-smoke.php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\DashboardStatsService;
use Illuminate\Support\Facades\Auth;

Auth::loginUsingId(1);

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

function hit($httpKernel, string $uri): array
{
    $response = $httpKernel->handle(Illuminate\Http\Request::create($uri, 'GET'));
    return [$response->getStatusCode(), $response->getContent()];
}

$failures = 0;

echo "--- Service layer ---\n";
$stats = DashboardStatsService::getStats(now()->format('Y-m'), false);
$required = [
    'month', 'month_name', 'expected', 'received', 'pending', 'carried_overdue',
    'total_overdue', 'defaulters_count', 'collection_rate', 'active_students',
    'running_batches', 'new_joins', 'expenses', 'net_profit', 'upcoming_dues',
    'top_defaulters', 'method_breakdown', 'batches_progress', 'recent_payments', 'trend',
];
foreach ($required as $key) {
    $ok = array_key_exists($key, $stats);
    echo '  ' . ($ok ? 'PASS' : 'FAIL') . "  stats.{$key}\n";
    if (! $ok) {
        $failures++;
    }
}
echo sprintf(
    "  INFO  %s | expected Rs %s | received Rs %s | rate %s%% | defaulters %d\n",
    $stats['month_name'],
    number_format($stats['expected'], 0),
    number_format($stats['received'], 0),
    $stats['collection_rate'],
    $stats['defaulters_count']
);
echo '  INFO  trend points: ' . count($stats['trend']['labels']) . "\n";
echo '  INFO  batches: ' . count($stats['batches_progress']) . ' | recent payments: ' . count($stats['recent_payments']) . "\n";
echo '  INFO  upcoming dues: ' . count($stats['upcoming_dues']) . ' | top defaulters: ' . count($stats['top_defaulters']) . "\n";

// Cached round-trip must be identical in shape (no incomplete objects).s
$cached = DashboardStatsService::getStats(now()->format('Y-m'), true);
$roundTrip = json_encode($cached) === json_encode($stats);
echo '  ' . ($roundTrip ? 'PASS' : 'FAIL') . "  cache round-trip matches uncached shape\n";
if (! $roundTrip) {
    $failures++;
}

echo "--- HTTP render ---\n";
[$status, $html] = hit($httpKernel, '/dashboard');
$ok = $status === 200;
echo '  ' . ($ok ? 'PASS' : 'FAIL') . "  /dashboard -> {$status} (" . strlen($html) . " bytes)\n";
if (! $ok) {
    $failures++;
}

$markers = [
    'Expected vs Received' => 'hero card',
    'id="trendChart"' => '12-month trend canvas',
    'id="collectionRing"' => 'collection rate ring',
    'Batch-wise Collection' => 'batch progress list',
    'Upcoming Dues' => 'upcoming dues list',
    'Top Overdue Students' => 'overdue list',
    'Recent Payments' => 'recent payments feed',
    'Net Profit' => 'net profit card',
    'countUp(' => 'animated count-up binding',
    'window.Chart' => 'Chart.js global',
];

// The methods donut only renders when the selected month has payments.
$hasMethods = array_sum($stats['method_breakdown']) > 0;
$markers[$hasMethods ? 'id="methodsChart"' : 'No payments recorded in this month'] = 'payment methods donut / empty state';

foreach ($markers as $needle => $label) {
    $found = str_contains($html, $needle);
    echo '  ' . ($found ? 'PASS' : 'FAIL') . "  {$label}\n";
    if (! $found) {
        $failures++;
    }
}

// Structural sanity: <div> open/close balance
$opens = substr_count($html, '<div');
$closes = substr_count($html, '</div>');
$balanced = $opens === $closes;
echo '  ' . ($balanced ? 'PASS' : 'WARN') . "  div balance: {$opens} open / {$closes} close\n";
if (! $balanced) {
    $failures++;
}

echo "--- Month with payments (method donut) ---\n";
$paidMonth = Illuminate\Support\Facades\DB::table('payments')
    ->whereNull('deleted_at')
    ->selectRaw("DATE_FORMAT(paid_on, '%Y-%m') as ym")
    ->groupBy('ym')
    ->orderByDesc('ym')
    ->value('ym');

if ($paidMonth) {
    [$pStatus, $pBody] = hit($httpKernel, '/dashboard?month=' . $paidMonth);
    $donutOk = $pStatus === 200 && str_contains($pBody, 'id="methodsChart"');
    echo '  ' . ($donutOk ? 'PASS' : 'FAIL') . "  /dashboard?month={$paidMonth} renders methods donut\n";
    if (! $donutOk) {
        $failures++;
    }
} else {
    echo "  SKIP  no payments in database\n";
}

echo "--- Month param handling ---\n";
foreach (['2026-01', '2026-13', 'garbage', '1899-05', '9999-12'] as $m) {
    [$st] = hit($httpKernel, '/dashboard?month=' . $m);
    [$st2, $body2] = hit($httpKernel, '/dashboard?month=' . $m);
    $okm = $st2 === 200;
    echo '  ' . ($okm ? 'PASS' : 'FAIL') . "  ?month={$m} -> {$st2}\n";
    if (! $okm) {
        $failures++;
    }
}

echo $failures === 0 ? "\nPHASE 7 SMOKE OK\n" : "\nPHASE 7 SMOKE FAILED ({$failures})\n";
exit($failures === 0 ? 0 : 1);
