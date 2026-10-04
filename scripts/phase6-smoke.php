<?php

// Phase 6 smoke test: Ledger matrix + Dues/Defaulters render end-to-end.
// Usage: C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe scripts/phase6-smoke.php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Batch;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

Auth::loginUsingId(1);

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

/**
 * Fire a request through the real HTTP kernel and report status.
 */
function hit($httpKernel, string $uri, bool $expectJson = false): bool
{
    $response = $httpKernel->handle(
        Illuminate\Http\Request::create($uri, 'GET', [], [], [], [
            'HTTP_ACCEPT' => $expectJson ? 'application/json' : 'text/html',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ])
    );

    $status = $response->getStatusCode();
    $body = $response->getContent();
    $ok = $status >= 200 && $status < 400;

    echo sprintf(
        "  %-6s %-38s %d bytes -> %s\n",
        $ok ? 'PASS' : 'FAIL',
        $uri,
        strlen($body),
        $status
    );

    return $ok;
}

$failures = 0;

echo "--- Ledger ---\n";
$batches = Batch::orderBy('start_date', 'desc')->get();
echo "  Batches available: " . $batches->count() . "\n";
foreach ($batches->take(3) as $b) {
    if (! hit($httpKernel, '/ledger?batch_id=' . $b->id)) {
        $failures++;
    }
    if (! hit($httpKernel, '/ledger')) {
        $failures++;
    }
    break;
}

// Cell drill-down JSON
$student = Student::whereNotNull('batch_id')->first();
if ($student) {
    $month = now()->format('Y-m');
    $json = $httpKernel->handle(
        Illuminate\Http\Request::create(
            '/ledger/cell?student_id=' . $student->id . '&month=' . $month,
            'GET',
            [], [], [], ['HTTP_ACCEPT' => 'application/json']
        )
    );
    $decoded = json_decode($json->getContent(), true);
    $ok = $json->getStatusCode() === 200 && isset($decoded['installments']);
    echo sprintf(
        "  %-6s %-38s installments=%d\n",
        $ok ? 'PASS' : 'FAIL',
        '/ledger/cell (json)',
        count($decoded['installments'] ?? [])
    );
    if (! $ok) {
        $failures++;
    }
}

echo "--- Dues ---\n";
if (! hit($httpKernel, '/dues')) {
    $failures++;
}
if ($batch = $batches->first()) {
    if (! hit($httpKernel, '/dues?batch_id=' . $batch->id)) {
        $failures++;
    }
}

// Dues derived-data sanity
$defaulters = Student::query()->defaulters()->get();
echo "  Defaulters found: " . $defaulters->count() . "\n";
if ($defaulters->isNotEmpty()) {
    $top = $defaulters->sortByDesc('days_overdue')->first();
    echo "  Top defaulter: {$top->name} | {$top->days_overdue} days | Rs " . number_format($top->overdue_amount, 0) . "\n";
    $url = App\Services\WhatsAppLinkBuilder::build(
        $top,
        App\Models\Setting::get('whatsapp_template_roman', App\Services\WhatsAppLinkBuilder::DEFAULT_TEMPLATE_URDU)
    );
    $ok = str_starts_with($url, 'https://wa.me/') && str_contains($url, 'wa.me');
    echo ($ok ? '  PASS' : '  FAIL') . ": Roman Urdu WhatsApp link builds\n";
    if (! $ok) {
        $failures++;
    }
}

echo $failures === 0 ? "\nPHASE 6 SMOKE OK\n" : "\nPHASE 6 SMOKE FAILED ({$failures})\n";
exit($failures === 0 ? 0 : 1);
