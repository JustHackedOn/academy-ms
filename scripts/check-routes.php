<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Illuminate\Support\Facades\Auth::loginUsingId(1);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$router = app('router');

$failures = 0;

foreach ($router->getRoutes() as $route) {
    if (! in_array('GET', $route->methods(), true)) {
        continue;
    }

    $uri = $route->uri();
    if (str_contains($uri, '{')) {
        continue;
    }

    $target = '/' . ltrim($uri, '/');
    $target = $target === '' ? '/' : $target;

    try {
        $status = $kernel->handle(Illuminate\Http\Request::create($target, 'GET'))->getStatusCode();
    } catch (\Throwable $e) {
        $status = 500;
    }

    $ok = $status < 400;
    if (! $ok) {
        $failures++;
    }
    echo ($ok ? 'PASS' : 'FAIL') . "  {$target} -> {$status}\n";
}

echo $failures === 0 ? "ALL GET ROUTES OK\n" : "{$failures} failing\n";

// Parameterised routes backed by whatever is already seeded.
$batch = \App\Models\Batch::query()->orderBy('id')->value('id');
$student = \App\Models\Student::query()->orderBy('id')->value('id');
$payment = \App\Models\Payment::query()->orderBy('id')->value('id');
$note = \App\Models\StudentNote::query()->orderBy('id')->value('id');
$expense = \App\Models\Expense::query()->orderBy('id')->value('id');

$dynamic = array_filter([
    $batch ? "/batches/{$batch}" : null,
    $batch ? "/batches/{$batch}/edit" : null,
    $batch ? '/ledger?batch_id=' . $batch : null,
    $student ? "/students/{$student}" : null,
    $student ? "/students/{$student}/edit" : null,
    $payment ? "/payments/{$payment}/receipt" : null,
    $payment ? "/payments/{$payment}/receipt.pdf" : null,
    $payment ? "/payments/{$payment}/whatsapp" : null,
    $payment ? "/payments/{$payment}/edit" : null,
    $note ? "/activity?search=payment" : null,
    $expense ? '/expenses?category=Rent' : null,
]);

foreach ($dynamic as $target) {
    try {
        $status = $kernel->handle(Illuminate\Http\Request::create($target, 'GET'))->getStatusCode();
    } catch (\Throwable $e) {
        $status = 500;
    }
    $ok = $status < 400;
    if (! $ok) {
        $failures++;
    }
    echo ($ok ? 'PASS' : 'FAIL') . "  {$target} -> {$status}\n";
}

echo $failures === 0 ? "ALL OK\n" : "{$failures} failing\n";
exit($failures === 0 ? 0 : 1);
