<?php

namespace App\Modules\Diffusion\Jobs;

use App\Models\Campaign;
use App\Models\CampaignTarget;
use App\Models\User;
use App\Modules\Diffusion\Services\MatchingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AssignCampaignTargetsJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives maximum avant abandon.
     */
    public int $tries = 3;

    /**
     * Délais entre les tentatives en secondes : 60 s, 5 min, 15 min.
     */
    public array $backoff = [60, 300, 900];

    /**
     * Nom de la queue cible.
     */

    /**
     * @param int $campaignId Identifiant de la campagne à cibler
     */
    public function __construct(
        public readonly int $campaignId,
    ) {}

    /**
     * Assigne les abonnés éligibles à une campagne en bulk.
     *
     * Étapes :
     *   1. Récupérer la campagne avec ses paramètres de ciblage
     *   2. Charger tous les abonnés actifs avec profil complété
     *   3. Filtrer via MatchingService::matchesCriteria()
     *   4. Insérer/mettre à jour en bulk par chunks de 500 (upsert)
     *
     * L'upsert sur (campaign_id, subscriber_id) garantit l'idempotence :
     * relancer le job ne crée pas de doublons.
     *
     * @param MatchingService $matchingService
     */
    public function handle(MatchingService $matchingService): void
    {
        Log::info('AssignCampaignTargetsJob : démarrage', [
            'campaign_id' => $this->campaignId,
        ]);

        /** @var Campaign|null $campaign */
        $campaign = Campaign::find($this->campaignId);

        if ($campaign === null) {
            Log::warning('AssignCampaignTargetsJob : campagne introuvable', [
                'campaign_id' => $this->campaignId,
            ]);

            return;
        }

        $totalAssigned = 0;
        $now           = Carbon::now();
        // L'assignation expire à la date de fin de la campagne ou dans 30 jours
        $expiresAt = $campaign->ends_at ?? $now->copy()->addDays(30);

        // Traiter les abonnés par chunks pour économiser la mémoire
        User::where('role', 'subscriber')
            ->where('is_active', true)
            ->where('is_suspended', false)
            ->whereHas('profile', fn ($q) => $q->whereNotNull('profile_completed_at'))
            ->with('profile')
            ->chunkById(200, function ($subscribers) use ($campaign, $matchingService, $now, $expiresAt, &$totalAssigned) {
                $rows = [];

                foreach ($subscribers as $subscriber) {
                    $profile = $subscriber->profile;

                    if ($profile === null) {
                        continue;
                    }

                    $age = $matchingService->calculateAge(
                        $profile->date_of_birth?->toDateString()
                    );

                    // Réutiliser matchesCriteria via la réflexion n'est pas possible
                    // car la méthode est privée. On passe par getEligibleCampaigns
                    // serait trop coûteux. On appelle la méthode publique exposée
                    // pour ce besoin via un filtre minimal reconstruit ici.
                    if (! $this->subscriberMatchesCampaign($campaign, $profile, $age)) {
                        continue;
                    }

                    $rows[] = [
                        'campaign_id'   => $campaign->id,
                        'subscriber_id' => $subscriber->id,
                        'status'        => 'pending',
                        'assigned_at'   => $now,
                        'expires_at'    => $expiresAt,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }

                if (empty($rows)) {
                    return;
                }

                // Bulk upsert par chunks de 500
                foreach (array_chunk($rows, 500) as $chunk) {
                    CampaignTarget::upsert(
                        $chunk,
                        ['campaign_id', 'subscriber_id'],
                        ['status', 'updated_at']
                    );

                    $totalAssigned += count($chunk);
                }
            });

        Log::info('AssignCampaignTargetsJob : terminé', [
            'campaign_id'    => $this->campaignId,
            'total_assigned' => $totalAssigned,
        ]);
    }

    /**
     * Vérifie si un abonné correspond aux critères de ciblage d'une campagne.
     *
     * Duplication volontaire de la logique de MatchingService::matchesCriteria()
     * car cette méthode est privée et ne peut pas être appelée depuis l'extérieur.
     * Cette version est utilisée uniquement par le job d'assignation en masse.
     *
     * @param Campaign          $campaign  Campagne à évaluer
     * @param \App\Models\SubscriberProfile $profile   Profil de l'abonné
     * @param int|null          $age       Âge précalculé
     * @return bool
     */
    private function subscriberMatchesCampaign(Campaign $campaign, \App\Models\SubscriberProfile $profile, ?int $age): bool
    {
        $targeting = $campaign->targeting;

        if (empty($targeting)) {
            return true;
        }

        if (! empty($targeting['cities'])) {
            $cities = array_map('mb_strtolower', $targeting['cities']);
            if (! in_array(mb_strtolower((string) $profile->city), $cities, true)) {
                return false;
            }
        }

        if (! empty($targeting['genders'])) {
            $genders = array_map('mb_strtolower', $targeting['genders']);
            if (! in_array(mb_strtolower((string) $profile->gender), $genders, true)) {
                return false;
            }
        }

        if (isset($targeting['age_min']) && $targeting['age_min'] !== null) {
            if ($age === null || $age < (int) $targeting['age_min']) {
                return false;
            }
        }

        if (isset($targeting['age_max']) && $targeting['age_max'] !== null) {
            if ($age === null || $age > (int) $targeting['age_max']) {
                return false;
            }
        }

        if (! empty($targeting['operators'])) {
            $operators = array_map('mb_strtolower', $targeting['operators']);
            if (! in_array(mb_strtolower((string) $profile->operator), $operators, true)) {
                return false;
            }
        }

        if (! empty($targeting['interests'])) {
            $targetInterests     = array_map('mb_strtolower', $targeting['interests']);
            $subscriberInterests = array_map('mb_strtolower', (array) ($profile->interests ?? []));

            if (empty(array_intersect($targetInterests, $subscriberInterests))) {
                return false;
            }
        }

        // Dynamic criteria stored in subscriber_profiles.custom_fields
        $dynamicCriteria = \App\Models\AudienceCriterion::getActiveCriteria()
            ->filter(fn ($c) => $c->storage_column === null);

        $customFields = $profile->custom_fields ?? [];

        foreach ($dynamicCriteria as $criterion) {
            $targetValue = $targeting[$criterion->name] ?? null;
            if ($targetValue === null) {
                continue;
            }

            $profileValue = $customFields[$criterion->name] ?? null;
            if ($profileValue === null) {
                return false;
            }

            $matches = match ($criterion->type) {
                'select' => is_array($targetValue)
                    ? in_array(strtolower((string) $profileValue), array_map('strtolower', $targetValue), true)
                    : strtolower((string) $profileValue) === strtolower((string) $targetValue),
                'multiselect' => ! empty(array_intersect(
                    array_map('strtolower', (array) $profileValue),
                    array_map('strtolower', (array) $targetValue)
                )),
                'text' => is_array($targetValue)
                    ? in_array(strtolower((string) $profileValue), array_map('strtolower', $targetValue), true)
                    : str_contains(strtolower((string) $profileValue), strtolower((string) $targetValue)),
                'boolean' => (bool) $profileValue === (bool) $targetValue,
                default   => true,
            };

            if (! $matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * Gère l'échec définitif du job après épuisement des tentatives.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AssignCampaignTargetsJob : échec définitif', [
            'campaign_id' => $this->campaignId,
            'error'       => $exception->getMessage(),
        ]);
    }
}
