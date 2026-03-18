<?php

namespace App\Modules\Campaign\Services;

use App\Models\Campaign;
use App\Models\EscrowEntry;
use App\Modules\Campaign\Events\CampaignApproved;
use App\Modules\Campaign\Events\CampaignRejected;
use App\Modules\Campaign\Events\CampaignSubmitted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CampaignService
{
    public function __construct(
        private readonly MediaService $mediaService,
    ) {}

    /**
     * Crée une nouvelle campagne en statut draft pour un annonceur.
     *
     * @param array $data         Données validées (titre, format, budget, etc.)
     * @param int   $advertiserId Identifiant de l'annonceur
     * @return Campaign
     */
    public function create(array $data, int $advertiserId): Campaign
    {
        $costPerView = $data['cost_per_view'] ?? config('oonclick.cost_per_view', 100);
        $maxViews    = (int) floor($data['budget'] / $costPerView);

        return Campaign::create([
            'advertiser_id' => $advertiserId,
            'title'         => $data['title'],
            'description'   => $data['description'] ?? null,
            'format'        => $data['format'],
            'status'        => 'draft',
            'budget'        => $data['budget'],
            'cost_per_view' => $costPerView,
            'max_views'     => $maxViews,
            'views_count'   => 0,
            'starts_at'     => $data['starts_at'] ?? null,
            'ends_at'       => $data['ends_at'] ?? null,
            'targeting'     => $data['targeting'] ?? null,
        ]);
    }

    /**
     * Uploade un fichier média sur R2 et met à jour la campagne.
     *
     * @param Campaign     $campaign Campagne cible
     * @param UploadedFile $file     Fichier à uploader
     * @param string       $type     'media' ou 'thumbnail'
     * @return string                URL CDN du fichier uploadé
     */
    public function uploadMedia(Campaign $campaign, UploadedFile $file, string $type = 'media'): string
    {
        $folder = "campaigns/{$campaign->id}/{$type}";
        $result = $this->mediaService->upload($file, $folder);

        if ($type === 'thumbnail') {
            $campaign->thumbnail_url = $result['url'];
        } else {
            $campaign->media_url  = $result['url'];
            $campaign->media_path = $result['path'];
        }

        $campaign->save();

        return $result['url'];
    }

    /**
     * Soumet la campagne pour modération (draft → pending_review).
     *
     * @param Campaign $campaign
     * @return Campaign
     * @throws RuntimeException Si le statut n'est pas draft ou si aucun média n'est uploadé
     */
    public function submit(Campaign $campaign): Campaign
    {
        if ($campaign->status !== 'draft') {
            throw new RuntimeException('Seules les campagnes en brouillon peuvent être soumises.');
        }

        if (empty($campaign->media_url)) {
            throw new RuntimeException('Aucun média uploadé. Veuillez ajouter un média avant de soumettre.');
        }

        $campaign->status = 'pending_review';
        $campaign->save();

        event(new CampaignSubmitted($campaign));

        return $campaign;
    }

    /**
     * Approuve une campagne (pending_review → approved).
     *
     * @param Campaign $campaign
     * @param int      $adminId  Identifiant de l'administrateur qui approuve
     * @return Campaign
     * @throws RuntimeException Si le statut n'est pas pending_review
     */
    public function approve(Campaign $campaign, int $adminId): Campaign
    {
        if ($campaign->status !== 'pending_review') {
            throw new RuntimeException('Seules les campagnes en attente de validation peuvent être approuvées.');
        }

        $campaign->status      = 'approved';
        $campaign->approved_at = now();
        $campaign->approved_by = $adminId;
        $campaign->save();

        event(new CampaignApproved($campaign));

        $campaign->advertiser->notify(
            new \App\Notifications\CampaignStatusNotification($campaign, 'approved')
        );

        return $campaign;
    }

    /**
     * Rejette une campagne (pending_review → rejected).
     *
     * @param Campaign $campaign
     * @param int      $adminId  Identifiant de l'administrateur qui rejette
     * @param string   $reason   Motif du rejet
     * @return Campaign
     * @throws RuntimeException Si le statut n'est pas pending_review
     */
    public function reject(Campaign $campaign, int $adminId, string $reason): Campaign
    {
        if ($campaign->status !== 'pending_review') {
            throw new RuntimeException('Seules les campagnes en attente de validation peuvent être rejetées.');
        }

        $campaign->status           = 'rejected';
        $campaign->rejection_reason = $reason;
        $campaign->save();

        event(new CampaignRejected($campaign));

        $campaign->advertiser->notify(
            new \App\Notifications\CampaignStatusNotification($campaign, 'rejected')
        );

        return $campaign;
    }

    /**
     * Active une campagne approuvée (approved → active).
     * Nécessite un escrow verrouillé.
     *
     * @param Campaign $campaign
     * @return Campaign
     * @throws RuntimeException Si le statut n'est pas approved ou si l'escrow est absent/invalide
     */
    public function activate(Campaign $campaign): Campaign
    {
        if ($campaign->status !== 'approved') {
            throw new RuntimeException('Seules les campagnes approuvées peuvent être activées.');
        }

        $escrow = EscrowEntry::where('campaign_id', $campaign->id)
            ->whereIn('status', ['locked', 'partial'])
            ->first();

        if (! $escrow) {
            throw new RuntimeException('Le budget de la campagne doit être verrouillé en escrow avant activation.');
        }

        $campaign->status = 'active';
        $campaign->save();

        return $campaign;
    }

    /**
     * Met en pause une campagne active (active → paused).
     *
     * @param Campaign $campaign
     * @return Campaign
     * @throws RuntimeException Si le statut n'est pas active
     */
    public function pause(Campaign $campaign): Campaign
    {
        if ($campaign->status !== 'active') {
            throw new RuntimeException('Seules les campagnes actives peuvent être mises en pause.');
        }

        $campaign->status = 'paused';
        $campaign->save();

        return $campaign;
    }

    /**
     * Reprend une campagne en pause (paused → active).
     * Nécessite un escrow toujours valide.
     *
     * @param Campaign $campaign
     * @return Campaign
     * @throws RuntimeException Si le statut n'est pas paused ou si l'escrow est invalide
     */
    public function resume(Campaign $campaign): Campaign
    {
        if ($campaign->status !== 'paused') {
            throw new RuntimeException('Seules les campagnes en pause peuvent être reprises.');
        }

        $escrow = EscrowEntry::where('campaign_id', $campaign->id)
            ->whereIn('status', ['locked', 'partial'])
            ->first();

        if (! $escrow) {
            throw new RuntimeException('Le budget escrow de la campagne n\'est plus valide. Impossible de reprendre.');
        }

        $campaign->status = 'active';
        $campaign->save();

        return $campaign;
    }

    /**
     * Complète une campagne et met à jour l'escrow en conséquence.
     *
     * @param Campaign $campaign
     * @return Campaign
     */
    public function complete(Campaign $campaign): Campaign
    {
        $campaign->status = 'completed';
        $campaign->save();

        $escrow = EscrowEntry::where('campaign_id', $campaign->id)->first();

        if ($escrow) {
            $newStatus      = $escrow->amount_released > 0 ? 'released' : 'refunded';
            $escrow->status = $newStatus;
            $escrow->save();
        }

        return $campaign;
    }
}
