<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tarifs plateforme (FCFA)
    | NE JAMAIS hardcoder ces valeurs dans le code applicatif.
    | Utiliser PlatformConfig::get('key') pour les valeurs dynamiques.
    |--------------------------------------------------------------------------
    */
    'cost_per_view'       => env('COST_PER_VIEW', 100),
    'subscriber_earn'     => env('SUBSCRIBER_EARN', 60),
    'platform_fee'        => env('PLATFORM_FEE', 40),

    'signup_bonus'        => env('SIGNUP_BONUS', 500),
    'referral_bonus'      => env('REFERRAL_BONUS', 200),

    'min_withdrawal'      => env('MIN_WITHDRAWAL', 5000),
    'withdrawal_fee'      => env('WITHDRAWAL_FEE', 0),

    /*
    |--------------------------------------------------------------------------
    | Limites anti-fraude
    |--------------------------------------------------------------------------
    */
    'max_views_per_hour'  => env('MAX_VIEWS_PER_HOUR', 10),
    'max_views_per_day'   => env('MAX_VIEWS_PER_DAY', 30),
    'min_trust_score'     => env('MIN_TRUST_SCORE', 40),
    'min_watch_percent'   => env('MIN_WATCH_PERCENT', 80),

    /*
    |--------------------------------------------------------------------------
    | KYC — plafonds de retrait par niveau (FCFA)
    |--------------------------------------------------------------------------
    */
    'kyc_level1_max_withdrawal' => env('KYC_L1_MAX', 10000),
    'kyc_level2_max_withdrawal' => env('KYC_L2_MAX', 100000),
    'kyc_level3_max_withdrawal' => env('KYC_L3_MAX', 1000000),

    /*
    |--------------------------------------------------------------------------
    | OTP
    |--------------------------------------------------------------------------
    */
    'otp_expires_minutes' => env('OTP_EXPIRES_MINUTES', 10),
    'otp_max_attempts'    => env('OTP_MAX_ATTEMPTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Formats publicitaires — bonus par format
    |--------------------------------------------------------------------------
    */
    'format_multipliers' => [
        'video'   => 1.0,
        'scratch' => 1.5,
        'quiz'    => 1.3,
        'flash'   => 1.2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloudflare R2 / CDN
    |--------------------------------------------------------------------------
    */
    'cdn_url'             => env('CLOUDFLARE_CDN_URL', ''),
    'media_disk'          => env('MEDIA_DISK', 'r2'),

    /*
    |--------------------------------------------------------------------------
    | Paystack
    |--------------------------------------------------------------------------
    */
    'paystack' => [
        'secret_key'      => env('PAYSTACK_SECRET_KEY'),
        'public_key'      => env('PAYSTACK_PUBLIC_KEY'),
        'webhook_secret'  => env('PAYSTACK_WEBHOOK_SECRET'),
        'base_url'        => 'https://api.paystack.co',
    ],

];
