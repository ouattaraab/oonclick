<?php

namespace App\Modules\Diffusion;

use Illuminate\Support\ServiceProvider;

class DiffusionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes.php');
    }
}
