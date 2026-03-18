<?php

namespace App\Modules\Diffusion\Services;

use App\Models\Campaign;
use App\Models\SubscriberProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class MatchingService
{
    /**
     * Retourne les campagnes actives et éligibles pour un abonné donné.
     *
     * L'algorithme :
     *   1. Récupère les campagnes actives dans la fenêtre temporelle valide
     *      dont le quota de vues n'est pas encore atteint.
     *   2. Exclut les campagnes déjà complètement visionnées par l'abonné.
     *   3. Exclut les campagnes déjà marquées 'viewed' dans campaign_targets.
     *   4. Filtre en PHP selon les critères de ciblage du profil.
     *   5. Limite à 10 campagnes, triées par vues_count ASC (les moins diffusées
     *      en priorité pour assurer une distribution équitable du budget).
     *
     * @param User $subscriber Abonné authentifié
     * @return Collection<int, Campaign>
     */
    public function getEligibleCampaigns(User $subscriber): Collection
    {
        $profile = $subscriber->profile;
        $age     = $this->calculateAge($profile?->date_of_birth?->toDateString());

        // 1. Requête de base : campagnes actives, dans la fenêtre temporelle, quota non atteint
        $campaigns = Campaign::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->whereRaw('views_count < max_views')
            // 2. Exclure campagnes déjà complétées par cet abonné (via ad_views)
            ->whereNotIn('id', function ($sub) use ($subscriber) {
                $sub->select('campaign_id')
                    ->from('ad_views')
                    ->where('subscriber_id', $subscriber->id)
                    ->where('is_completed', true);
            })
            // 3. Exclure campagnes déjà marquées 'viewed' dans campaign_targets
            ->whereNotIn('id', function ($sub) use ($subscriber) {
                $sub->select('campaign_id')
                    ->from('campaign_targets')
                    ->where('subscriber_id', $subscriber->id)
                    ->where('status', 'viewed');
            })
            ->orderBy('views_count', 'asc')
            ->get();

        // 4. Filtrage ciblage en PHP pour la flexibilité
        if ($profile === null) {
            // Sans profil, seules les campagnes sans ciblage sont retournées
            return $campaigns
                ->filter(fn (Campaign $campaign) => empty($campaign->targeting))
                ->values()
                ->take(10);
        }

        return $campaigns
            ->filter(fn (Campaign $campaign) => $this->matchesCriteria($campaign, $profile, $age))
            ->values()
            ->take(10);
    }

    /**
     * Calcule l'âge en années à partir d'une date de naissance.
     *
     * @param string|null $dateOfBirth Date au format Y-m-d ou null
     * @return int|null                Âge calculé ou null si date absente
     */
    public function calculateAge(?string $dateOfBirth): ?int
    {
        if ($dateOfBirth === null) {
            return null;
        }

        return Carbon::parse($dateOfBirth)->age;
    }

    /**
     * Vérifie si un profil abonné satisfait les critères de ciblage d'une campagne.
     *
     * Si le ciblage est null ou vide, la campagne est considérée universelle et
     * inclut tous les abonnés. Chaque critère défini doit être individuellement
     * satisfait (logique ET).
     *
     * @param Campaign          $campaign Campagne à évaluer
     * @param SubscriberProfile $profile  Profil de l'abonné
     * @param int|null          $age      Âge précalculé de l'abonné
     * @return bool
     */
    private function matchesCriteria(Campaign $campaign, SubscriberProfile $profile, ?int $age): bool
    {
        $targeting = $campaign->targeting;

        // Aucun ciblage défini → campagne universelle
        if (empty($targeting)) {
            return true;
        }

        // Ciblage par ville (case-insensitive)
        if (! empty($targeting['cities'])) {
            $cities = array_map('mb_strtolower', $targeting['cities']);
            if (! in_array(mb_strtolower((string) $profile->city), $cities, true)) {
                return false;
            }
        }

        // Ciblage par genre
        if (! empty($targeting['genders'])) {
            $genders = array_map('mb_strtolower', $targeting['genders']);
            if (! in_array(mb_strtolower((string) $profile->gender), $genders, true)) {
                return false;
            }
        }

        // Ciblage par âge minimum
        if (isset($targeting['age_min']) && $targeting['age_min'] !== null) {
            if ($age === null || $age < (int) $targeting['age_min']) {
                return false;
            }
        }

        // Ciblage par âge maximum
        if (isset($targeting['age_max']) && $targeting['age_max'] !== null) {
            if ($age === null || $age > (int) $targeting['age_max']) {
                return false;
            }
        }

        // Ciblage par opérateur téléphonique
        if (! empty($targeting['operators'])) {
            $operators = array_map('mb_strtolower', $targeting['operators']);
            if (! in_array(mb_strtolower((string) $profile->operator), $operators, true)) {
                return false;
            }
        }

        // Ciblage par centres d'intérêt : au moins un intérêt en commun
        if (! empty($targeting['interests'])) {
            $targetInterests    = array_map('mb_strtolower', $targeting['interests']);
            $subscriberInterests = array_map('mb_strtolower', (array) ($profile->interests ?? []));

            if (empty(array_intersect($targetInterests, $subscriberInterests))) {
                return false;
            }
        }

        return true;
    }
}
