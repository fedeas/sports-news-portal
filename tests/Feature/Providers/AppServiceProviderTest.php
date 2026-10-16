<?php

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

afterEach(function () {
    TrustProxies::flushState();
});

it('reads the client IP and HTTPS from a configured trusted proxy', function (?string $trustedProxies, string $expectedIp, bool $expectedSecure) {
    config(['app.trusted_proxies' => $trustedProxies]);
    app()->getProvider(AppServiceProvider::class)->boot();
    Route::get('/_test/client', fn (Request $request) => ['ip' => $request->ip(), 'secure' => $request->isSecure()]);

    $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
        ->withHeaders(['X-Forwarded-For' => '203.0.113.7', 'X-Forwarded-Proto' => 'https'])
        ->getJson('/_test/client');

    $response->assertExactJson(['ip' => $expectedIp, 'secure' => $expectedSecure]);
})->with([
    'not configured' => [null, '10.0.0.5', false],
    'listed proxy' => ['10.0.0.1, 10.0.0.5', '203.0.113.7', true],
    'unlisted proxy' => ['10.0.0.1', '10.0.0.5', false],
    'any proxy' => ['*', '203.0.113.7', true],
]);

it('adds the scheduler and the configured local services to composer run dev', function () {
    config(['app.dev_services' => ['mysql' => 'start-mysql.cmd', 'redis' => null]]);
    Closure::bind(fn () => DevCommands::$commands = [], null, DevCommands::class)();

    app()->getProvider(AppServiceProvider::class)->boot();

    expect(array_column(DevCommands::commands(), 'command', 'name'))
        ->toMatchArray(['scheduler' => 'php artisan schedule:work', 'mysql' => 'start-mysql.cmd'])
        ->not->toHaveKey('redis');
});
