<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Notifications\CampaignApprovedNotification;
use App\Notifications\CampaignRejectedNotification;
use Illuminate\Http\Request;

class AdminCampaignsController extends Controller
{
    public function index()
    {
        $campaigns        = Campaign::with('advertiser')->latest()->paginate(15);
        $totalCampaigns   = Campaign::count();
        $pendingCampaigns = Campaign::where('status', 'pending_review')->count();
        $activeCampaigns  = Campaign::where('status', 'active')->count();
        $totalBudget      = Campaign::sum('budget');

        return view('panel.admin.campaigns', compact(
            'campaigns', 'totalCampaigns', 'pendingCampaigns', 'activeCampaigns', 'totalBudget'
        ));
    }

    public function show(Campaign $campaign)
    {
        $campaign->load('advertiser');

        return view('panel.admin.campaign-detail', compact('campaign'));
    }

    /**
     * Approuve une campagne et notifie l'annonceur par base de données et email.
     */
    public function approve(Campaign $campaign)
    {
        $campaign->update(['status' => 'active']);

        // Notifier l'annonceur de l'approbation
        if ($campaign->advertiser) {
            $campaign->advertiser->notify(new CampaignApprovedNotification($campaign));
        }

        return back()->with('success', "Campagne \"{$campaign->title}\" approuvée.");
    }

    /**
     * Rejette une campagne et notifie l'annonceur avec le motif de rejet.
     */
    public function reject(Campaign $campaign, Request $request)
    {
        $rejectionReason = $request->input('rejection_reason', '');

        $campaign->update([
            'status'           => 'rejected',
            'rejection_reason' => $rejectionReason ?: null,
        ]);

        // Notifier l'annonceur du rejet
        if ($campaign->advertiser) {
            $campaign->advertiser->notify(new CampaignRejectedNotification($campaign, $rejectionReason));
        }

        return back()->with('success', "Campagne \"{$campaign->title}\" rejetée.");
    }

    /**
     * Met une campagne active en pause.
     */
    public function pause(Campaign $campaign)
    {
        $campaign->update(['status' => 'paused']);

        return back()->with('success', "Campagne « {$campaign->title} » mise en pause.");
    }

    /**
     * Relance une campagne mise en pause.
     */
    public function resume(Campaign $campaign)
    {
        $campaign->update(['status' => 'active']);

        return back()->with('success', "Campagne « {$campaign->title} » relancée.");
    }
}
