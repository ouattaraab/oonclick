<?php

namespace App\Modules\Fraud;

use App\Modules\Fraud\Http\Middleware\CheckTrustScore;
use App\Modules\Fraud\Services\FraudDetectionService;
use App\Modules\Fraud\Services\TrustScoreService;
use Illuminate\Support\ServiceProvider;

class FraudServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Enregistrer l'alias du middleware trust.score
        $this->app['router']->aliasMiddleware('trust.score', CheckTrustScore::class);
    }

    public function register(): void
    {
        $this->app->singleton(TrustScoreService::class);
        $this->app->singleton(FraudDetectionService::class);
    }
}
