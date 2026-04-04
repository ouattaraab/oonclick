<?php

namespace App\Providers;

use App\Models\Campaign;
use App\Models\User;
use App\Models\Withdrawal;
use App\Modules\Campaign\Events\CampaignApproved;
use App\Modules\Diffusion\Jobs\AssignCampaignTargetsJob;
use App\Observers\CampaignObserver;
use App\Observers\UserObserver;
use App\Observers\WithdrawalObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        User::observe(UserObserver::class);
        Campaign::observe(CampaignObserver::class);
        Withdrawal::observe(WithdrawalObserver::class);

        // When a campaign is approved, assign eligible subscriber targets.
        Event::listen(CampaignApproved::class, function (CampaignApproved $event) {
            AssignCampaignTargetsJob::dispatch($event->campaign->id);
        });
    }
}
