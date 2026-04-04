<?php

return [
    App\Providers\AppServiceProvider::class,
    // Filament panels disabled — replaced by custom Blade panel at /panel/*
    // App\Providers\Filament\AdminPanelProvider::class,
    // App\Providers\Filament\AdvertiserPanelProvider::class,
    App\Providers\ModuleServiceProvider::class,
    App\Modules\Auth\AuthServiceProvider::class,
    App\Modules\Campaign\CampaignServiceProvider::class,
    App\Modules\Diffusion\DiffusionServiceProvider::class,
    App\Modules\Payment\PaymentServiceProvider::class,
    App\Modules\Fraud\FraudServiceProvider::class,
    App\Modules\Analytics\AnalyticsServiceProvider::class,
];
