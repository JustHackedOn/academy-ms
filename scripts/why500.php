<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Illuminate\Support\Facades\Auth::loginUsingId(1);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$targets = array_slice($argv, 1);

foreach ($targets as $target) {
    $response = $kernel->handle(Illuminate\Http\Request::create($target, 'GET'));
    $html = $response->getContent();
    echo "{$target} -> " . $response->getStatusCode() . "\n";

    foreach ([
        '/<div class="exception-message[^"]*">(.*?)<\/div>/s',
        '/<h1[^>]*>(.*?)<\/h1>/s',
        '/class="block-code[^"]*"[^>]*>(.*?)<\/div>/s',
        '/<p class="message">(.*?)<\/p>/s',
        '/title="([^"]*exception[^"]*)"/i',
    ] as $pattern) {
        if (preg_match($pattern, $html, $m)) {
            echo '    match: ' . trim(preg_replace('/\s+/', ' ', strip_tags($m[1]))) . "\n";
            break;
        }
    }

    // Fallback: look for any sentence containing typical PHP error wording.
    $plain = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($html))));
    if (preg_match('/(Undefined variable[^<]{0,120}|View \[[^\]]+\] not found|Class [^ ]+ not found|Call to undefined[^<]{0,120}|Syntax error[^<]{0,120}|Unable to locate[^<]{0,160})/', $plain, $m)) {
        echo '    text: ' . $m[1] . "\n";
    }
}
