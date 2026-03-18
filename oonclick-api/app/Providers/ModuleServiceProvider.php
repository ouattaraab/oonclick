<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Modules enregistrés dans l'application.
     * Chaque module peut avoir son propre ServiceProvider si nécessaire.
     */
    protected array $modules = [
        \App\Modules\Auth\AuthServiceProvider::class,
        \App\Modules\Campaign\CampaignServiceProvider::class,
        \App\Modules\Diffusion\DiffusionServiceProvider::class,
        \App\Modules\Payment\PaymentServiceProvider::class,
        \App\Modules\Fraud\FraudServiceProvider::class,
        \App\Modules\Analytics\AnalyticsServiceProvider::class,
    ];

    public function register(): void
    {
        foreach ($this->modules as $module) {
            if (class_exists($module)) {
                $this->app->register($module);
            }
        }
    }

    public function boot(): void
    {
        //
    }
}
