<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\AudienceCriterion;
use App\Models\Campaign;
use App\Models\CampaignFormat;
use App\Models\Coupon;
use App\Models\PartnerOffer;
use App\Models\User;
use App\Models\Wallet;
use App\Modules\Analytics\Services\CampaignAnalyticsService;
use App\Modules\Campaign\Jobs\ProcessCampaignPaymentJob;
use App\Modules\Campaign\Services\MediaService;
use App\Modules\Payment\Services\PaystackService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdvertiserCampaignsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $campaigns = Campaign::where('advertiser_id', $user->id)->latest()->paginate(15);
        $wallet = Wallet::where('user_id', $user->id)->first();

        return view('panel.advertiser.campaigns', [
            'campaigns'     => $campaigns,
            'walletBalance' => $wallet?->balance ?? 0,
        ]);
    }

    public function create()
    {
        $wallet   = Wallet::where('user_id', auth()->id())->first();
        $formats  = CampaignFormat::getActiveFormats();
        $criteria = AudienceCriterion::getActiveCriteria();

        return view('panel.advertiser.create-campaign', compact('formats', 'criteria') + [
            'walletBalance' => $wallet?->balance ?? 0,
        ]);
    }

    public function show(Campaign $campaign)
    {
        $user = auth()->user();

        abort_if($campaign->advertiser_id !== $user->id, 403);

        $wallet = Wallet::where('user_id', $user->id)->first();

        return view('panel.advertiser.campaign-detail', [
            'campaign'      => $campaign,
            'walletBalance' => $wallet?->balance ?? 0,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string|max:2000',
            'format'              => ['required', 'string', Rule::exists('campaign_formats', 'slug')->where('is_active', true)],
            'end_mode'            => 'nullable|in:date,target_reached,manual',
            'duration_seconds'    => 'nullable|integer|min:5|max:120',
            'budget'              => 'required|integer|min:5000',
            'cost_per_view'       => 'nullable|integer|min:100',
            'starts_at'           => 'nullable|date',
            'ends_at'             => 'nullable|date|after:starts_at',
            // Targeting
            'targeting'              => 'nullable|array',
            'targeting.cities'       => 'nullable|array',
            'targeting.cities.*'     => 'nullable|string',
            'targeting.genders'      => 'nullable|array',
            'targeting.genders.*'    => 'nullable|string',
            'targeting.age_min'      => 'nullable|integer|min:13|max:99',
            'targeting.age_max'      => 'nullable|integer|min:13|max:99',
            'targeting.operators'    => 'nullable|array',
            'targeting.operators.*'  => 'nullable|string',
            'targeting.interests'    => 'nullable|array',
            'targeting.interests.*'  => 'nullable|string',
            // Dynamic audience criteria keys
            'targeting.*'            => 'nullable',
            // Quiz data
            'quiz_data'           => 'nullable|json',
            // Files
            'media'               => 'nullable|file|mimes:mp4,mov,avi,webm,jpg,jpeg,png,gif|max:51200',
            'thumbnail'           => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $costPerView = $data['cost_per_view'] ?? config('oonclick.cost_per_view', 100);
        $maxViews    = (int) floor($data['budget'] / $costPerView);

        $campaign = Campaign::create([
            'advertiser_id'    => $user->id,
            'title'            => $data['title'],
            'description'      => $data['description'] ?? null,
            'format'           => $data['format'],
            'end_mode'         => $data['end_mode'] ?? 'target_reached',
            'status'           => 'draft',
            'budget'           => $data['budget'],
            'cost_per_view'    => $costPerView,
            'max_views'        => $maxViews,
            'views_count'      => 0,
            'duration_seconds' => $data['duration_seconds'] ?? 30,
            'targeting'        => $data['targeting'] ?? null,
            'quiz_data'        => isset($data['quiz_data']) ? json_decode($data['quiz_data'], true) : null,
            'starts_at'        => $data['starts_at'] ?? null,
            'ends_at'          => $data['ends_at'] ?? null,
        ]);

        // Handle media upload
        if ($request->hasFile('media')) {
            $mediaService = app(MediaService::class);
            $result = $mediaService->upload($request->file('media'), "campaigns/{$campaign->id}/media");
            $campaign->update([
                'media_path' => $result['path'],
                'media_url'  => $result['url'],
            ]);
        }

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $mediaService = app(MediaService::class);
            $result = $mediaService->upload($request->file('thumbnail'), "campaigns/{$campaign->id}/thumbnail");
            $campaign->update([
                'thumbnail_url' => $result['url'],
            ]);
        }

        return redirect()->route('panel.advertiser.campaigns.show', $campaign)
            ->with('success', 'Campagne créée avec succès ! Procédez au paiement pour la publier.');
    }

    /**
     * Estimates the reachable audience based on targeting criteria (AJAX).
     */
    public function estimateAudience(Request $request): JsonResponse
    {
        $query = User::where('role', 'subscriber')
            ->where('is_active', true)
            ->where('is_suspended', false)
            ->whereHas('profile', function ($q) {
                $q->whereNotNull('profile_completed_at');
            });

        $targeting = $request->input('targeting', []);

        if (!empty($targeting['cities'])) {
            $query->whereHas('profile', fn ($q) =>
                $q->whereIn('city', $targeting['cities'])
            );
        }

        if (!empty($targeting['genders'])) {
            $query->whereHas('profile', fn ($q) =>
                $q->whereIn('gender', $targeting['genders'])
            );
        }

        if (!empty($targeting['age_min']) || !empty($targeting['age_max'])) {
            $query->whereHas('profile', function ($q) use ($targeting) {
                if (!empty($targeting['age_min'])) {
                    $q->where('date_of_birth', '<=', now()->subYears($targeting['age_min']));
                }
                if (!empty($targeting['age_max'])) {
                    $q->where('date_of_birth', '>=', now()->subYears($targeting['age_max'] + 1));
                }
            });
        }

        if (!empty($targeting['operators'])) {
            $query->whereHas('profile', fn ($q) =>
                $q->whereIn('operator', $targeting['operators'])
            );
        }

        // Critères dynamiques depuis custom_fields
        $dynamicCriteria = AudienceCriterion::getActiveCriteria()
            ->filter(fn ($c) => $c->storage_column === null);

        foreach ($dynamicCriteria as $criterion) {
            $targetValue = $targeting[$criterion->name] ?? null;
            if ($targetValue === null) continue;

            $query->whereHas('profile', function ($q) use ($criterion, $targetValue) {
                if (is_array($targetValue)) {
                    $q->where(function ($sub) use ($criterion, $targetValue) {
                        foreach ($targetValue as $v) {
                            $sub->orWhereJsonContains("custom_fields->{$criterion->name}", $v);
                        }
                    });
                } else {
                    $q->where("custom_fields->{$criterion->name}", $targetValue);
                }
            });
        }

        $count = $query->count();
        $total = User::where('role', 'subscriber')->where('is_active', true)->count();

        return response()->json([
            'estimated_audience' => $count,
            'total_subscribers'  => $total,
            'percentage'         => $total > 0 ? round(($count / $total) * 100) : 0,
        ]);
    }

    /**
     * Initiates a Paystack payment for a campaign budget.
     */
    public function initiatePayment(Campaign $campaign)
    {
        $user = auth()->user();

        if ($campaign->advertiser_id !== $user->id) {
            abort(403);
        }

        if (!in_array($campaign->status, ['draft', 'approved'])) {
            return back()->withErrors(['payment' => 'Cette campagne ne peut pas être payée dans son état actuel.']);
        }

        $reference      = 'CAMP-' . $campaign->id . '-' . Str::random(10);
        $paystackService = app(PaystackService::class);

        try {
            $result = $paystackService->initializePayment(
                $user->id,
                $campaign->budget,
                $reference,
                ['campaign_id' => $campaign->id, 'type' => 'campaign_payment']
            );

            session(['campaign_payment_ref' => $reference, 'campaign_payment_id' => $campaign->id]);

            return redirect($result['authorization_url']);
        } catch (\Exception $e) {
            return back()->withErrors(['payment' => 'Erreur de paiement : ' . $e->getMessage()]);
        }
    }

    /**
     * Handles the Paystack payment callback and dispatches the processing job.
     */
    public function paymentCallback(Request $request)
    {
        $reference  = $request->query('reference') ?? session('campaign_payment_ref');
        $campaignId = session('campaign_payment_id');

        if (!$reference || !$campaignId) {
            return redirect()->route('panel.advertiser.campaigns')
                ->withErrors(['payment' => 'Référence de paiement invalide.']);
        }

        ProcessCampaignPaymentJob::dispatch($reference, $campaignId);

        session()->forget(['campaign_payment_ref', 'campaign_payment_id']);

        return redirect()->route('panel.advertiser.campaigns.show', $campaignId)
            ->with('success', 'Paiement en cours de traitement. Votre campagne sera activée sous peu.');
    }

    /**
     * Duplicates a campaign as a new draft (US-025).
     */
    public function duplicate(Campaign $campaign)
    {
        abort_if($campaign->advertiser_id !== auth()->id(), 403);

        Campaign::create([
            'advertiser_id'    => auth()->id(),
            'title'            => $campaign->title . ' (copie)',
            'description'      => $campaign->description,
            'format'           => $campaign->format,
            'status'           => 'draft',
            'budget'           => $campaign->budget,
            'cost_per_view'    => $campaign->cost_per_view,
            'max_views'        => $campaign->max_views,
            'views_count'      => 0,
            'duration_seconds' => $campaign->duration_seconds,
            'targeting'        => $campaign->targeting,
            'quiz_data'        => $campaign->quiz_data,
            'thumbnail_url'    => $campaign->thumbnail_url,
            // media_path and media_url intentionally omitted (re-upload required)
        ]);

        return redirect()
            ->route('panel.advertiser.campaigns')
            ->with('success', 'Campagne dupliquée avec succès. Elle est en brouillon, pensez à uploader un nouveau média.');
    }

    /**
     * Returns current campaign progress as JSON (polling fallback for real-time updates).
     */
    public function progress(Campaign $campaign): JsonResponse
    {
        $user = auth()->user();

        if ($campaign->advertiser_id !== $user->id) {
            abort(403);
        }

        return response()->json([
            'views_count'     => $campaign->views_count,
            'max_views'       => $campaign->max_views,
            'budget_used'     => $campaign->views_count * $campaign->cost_per_view,
            'budget'          => $campaign->budget,
            'remaining_views' => max(0, $campaign->max_views - $campaign->views_count),
            'status'          => $campaign->status,
            'percentage'      => $campaign->max_views > 0
                ? round(($campaign->views_count / $campaign->max_views) * 100, 1)
                : 0,
        ]);
    }

    /**
     * Generates and downloads the campaign PDF report (US-056).
     */
    public function downloadPdf(Campaign $campaign)
    {
        abort_if($campaign->advertiser_id !== auth()->id(), 403);

        $analyticsService = app(CampaignAnalyticsService::class);
        $stats = $analyticsService->getStats($campaign->id);

        $pdf = Pdf::loadView('pdf.campaign-report', [
            'campaign'    => $campaign,
            'stats'       => $stats,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("rapport-campagne-{$campaign->id}.pdf");
    }

    /**
     * Lists coupons linked to this advertiser's campaigns.
     */
    public function coupons()
    {
        $user = auth()->user();
        $walletBalance = $user->wallet?->balance ?? 0;

        $campaignIds = Campaign::where('advertiser_id', $user->id)->pluck('id');
        $coupons     = Coupon::whereIn('campaign_id', $campaignIds)->latest()->paginate(15);
        $campaigns   = Campaign::where('advertiser_id', $user->id)->get(['id', 'title']);

        return view('panel.advertiser.coupons', compact('coupons', 'campaigns', 'walletBalance'));
    }

    /**
     * Stores a new coupon for one of this advertiser's campaigns.
     */
    public function storeCoupon(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'campaign_id'    => 'required|exists:campaigns,id',
            'code'           => 'required|string|max:50',
            'description'    => 'nullable|string|max:255',
            'discount_type'  => 'required|in:percent,fixed',
            'discount_value' => 'required|integer|min:1',
            'partner_name'   => 'required|string|max:100',
            'expires_at'     => 'nullable|date|after:today',
            'max_uses'       => 'nullable|integer|min:1',
        ]);

        // Verify the campaign belongs to this advertiser
        Campaign::where('id', $data['campaign_id'])
            ->where('advertiser_id', $user->id)
            ->firstOrFail();

        Coupon::create($data + ['is_active' => true, 'uses_count' => 0]);

        return back()->with('success', 'Coupon créé avec succès.');
    }

    /**
     * Lists active partner cashback offers visible to the advertiser.
     */
    public function offers()
    {
        $user = auth()->user();
        $walletBalance = $user->wallet?->balance ?? 0;

        $offers = PartnerOffer::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest()
            ->paginate(15);

        return view('panel.advertiser.offers', compact('offers', 'walletBalance'));
    }
}
