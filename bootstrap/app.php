<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\SetSecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetSecurityHeaders::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Answer only for the domain in APP_URL and its subdomains, so a forged Host header can
        // never end up in a password reset link. Off locally and in tests.
        $middleware->trustHosts();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
