<?php

/**
 * Sentry SDK configuration for oon.click Laravel API.
 *
 * DSN is read from the SENTRY_LARAVEL_DSN environment variable.
 * Leave the variable empty (or unset) to disable Sentry entirely —
 * useful for local development and CI pipelines.
 *
 * @see https://docs.sentry.io/platforms/php/guides/laravel/
 */
return [

    // -------------------------------------------------------------------------
    // DSN
    // -------------------------------------------------------------------------

    'dsn' => env('SENTRY_LARAVEL_DSN', ''),

    // -------------------------------------------------------------------------
    // Release tracking
    // Automatically set to the current git SHA in CI via the SENTRY_RELEASE
    // environment variable. Falls back to the app version string.
    // -------------------------------------------------------------------------

    'release' => env('SENTRY_RELEASE', config('app.version', '1.0.0')),

    // -------------------------------------------------------------------------
    // Environment
    // -------------------------------------------------------------------------

    'environment' => env('APP_ENV', 'production'),

    // -------------------------------------------------------------------------
    // Performance monitoring
    // traces_sample_rate: fraction of transactions to capture (0.0 – 1.0).
    //   - 1.0 in local/staging to capture everything.
    //   - 0.1 in production to reduce overhead.
    // -------------------------------------------------------------------------

    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    // -------------------------------------------------------------------------
    // Error sampling
    // Set to 1.0 to always send errors, lower values to reduce volume on
    // high-traffic endpoints.
    // -------------------------------------------------------------------------

    'sample_rate' => (float) env('SENTRY_SAMPLE_RATE', 1.0),

    // -------------------------------------------------------------------------
    // Breadcrumbs
    // -------------------------------------------------------------------------

    'breadcrumbs' => [
        // Include log messages as breadcrumbs.
        'logs'                 => true,
        // Include Laravel queue job lifecycle events.
        'queue_info'           => true,
        // Include command lifecycle events.
        'command_info'         => true,
        // Include HTTP client request breadcrumbs.
        'http_client_requests' => true,
    ],

    // -------------------------------------------------------------------------
    // Integrations
    // -------------------------------------------------------------------------

    'tracing' => [
        // Queue monitoring
        'queue_job_transactions'  => true,
        'queue_jobs'              => true,
        // Cache monitoring
        'cache_spans'             => true,
        // Eloquent query spans
        'sql_queries'             => true,
        'sql_bindings'            => false, // Disable in production for privacy
        // HTTP client spans
        'http_client_requests'    => true,
        // Redis spans
        'redis_commands'          => false,
        // View rendering spans
        'views'                   => true,
    ],

    // -------------------------------------------------------------------------
    // Data scrubbing — PII fields that must never be sent to Sentry
    // -------------------------------------------------------------------------

    'before_send' => null,

    // -------------------------------------------------------------------------
    // User context
    // Attach the authenticated user's id and role to every event.
    // Sentry will NOT include email/phone unless you explicitly add them.
    // -------------------------------------------------------------------------

    'send_default_pii' => false,

];
