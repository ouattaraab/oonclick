<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);

        $middleware->redirectGuestsTo('/panel/login');

        $middleware->alias([
            'trust.score'              => \App\Modules\Fraud\Http\Middleware\CheckTrustScore::class,
            'paystack.webhook'         => \App\Http\Middleware\VerifyPaystackWebhook::class,
            'role.subscriber'          => \App\Http\Middleware\EnsureSubscriber::class,
            'role.advertiser'          => \App\Http\Middleware\EnsureAdvertiser::class,
            'role.advertiser_or_admin' => \App\Http\Middleware\EnsureAdvertiserOrAdmin::class,
            'audit.admin'              => \App\Http\Middleware\AuditAdminMiddleware::class,
            'admin'                    => \App\Http\Middleware\EnsureAdmin::class,
            'advertiser'               => \App\Http\Middleware\EnsureAdvertiser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Forward unhandled exceptions to Sentry when a DSN is configured.
        // This is a no-op when SENTRY_LARAVEL_DSN is empty, so local
        // development and CI pipelines are unaffected.
        if (app()->bound(\Sentry\Laravel\Integration::class)) {
            $exceptions->reportable(function (\Throwable $e): void {
                \Sentry\Laravel\Integration::captureUnhandledException($e);
            });
        }
    })->create();
