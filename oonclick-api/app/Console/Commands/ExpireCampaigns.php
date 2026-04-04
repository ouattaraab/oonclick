<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use Illuminate\Console\Command;

class ExpireCampaigns extends Command
{
    protected $signature = 'oonclick:expire-campaigns';
    protected $description = 'Auto-stop campaigns that have reached their end date or max views';

    public function handle(): int
    {
        // Stop campaigns past end date
        $dateExpired = Campaign::where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->update(['status' => 'completed']);

        // Stop campaigns that reached max views
        $viewsExpired = Campaign::where('status', 'active')
            ->whereColumn('views_count', '>=', 'max_views')
            ->where('max_views', '>', 0)
            ->update(['status' => 'completed']);

        $this->info("Expired: {$dateExpired} by date, {$viewsExpired} by views.");
        return 0;
    }
}
