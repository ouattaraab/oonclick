<?php

namespace App\Modules\Payment;

use App\Modules\Payment\Services\EscrowService;
use App\Modules\Payment\Services\PaystackService;
use App\Modules\Payment\Services\WalletService;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaystackService::class, fn () => new PaystackService());
        $this->app->singleton(EscrowService::class, fn () => new EscrowService());
        $this->app->singleton(WalletService::class, fn () => new WalletService());
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
    }
}
