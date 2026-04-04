<?php

namespace App\Modules\Diffusion\Services;

use App\Models\AudienceCriterion;
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
            ->whereColumn('views_count', '<', 'max_views')
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

        // Critères dynamiques (custom_fields sur subscriber_profiles)
        $dynamicCriteria = AudienceCriterion::getActiveCriteria()
            ->filter(fn ($c) => $c->storage_column === null); // Uniquement les non-builtin

        foreach ($dynamicCriteria as $criterion) {
            $targetValue = $targeting[$criterion->name] ?? null;
            if ($targetValue === null) continue; // La campagne ne cible pas ce critère

            $profileValue = $profile->custom_fields[$criterion->name] ?? null;
            if ($profileValue === null) return false; // L'abonné n'a pas renseigné ce champ

            $matches = match ($criterion->type) {
                'select' => is_array($targetValue)
                    ? in_array(strtolower($profileValue), array_map('strtolower', $targetValue))
                    : strtolower($profileValue) === strtolower($targetValue),
                'multiselect' => ! empty(array_intersect(
                    array_map('strtolower', (array) $profileValue),
                    array_map('strtolower', (array) $targetValue)
                )),
                'text' => is_array($targetValue)
                    ? in_array(strtolower($profileValue), array_map('strtolower', $targetValue))
                    : str_contains(strtolower($profileValue), strtolower($targetValue)),
                'boolean' => (bool) $profileValue === (bool) $targetValue,
                'number'  => $this->matchNumberCriterion($profileValue, $targetValue),
                'range'   => $this->matchRangeCriterion($profileValue, $targetValue),
                default   => true,
            };

            if (! $matches) return false;
        }

        return true;
    }

    /**
     * Vérifie si une valeur numérique du profil correspond au ciblage number.
     */
    private function matchNumberCriterion(mixed $profileValue, mixed $targetValue): bool
    {
        if (is_array($targetValue)) {
            return in_array((int) $profileValue, array_map('intval', $targetValue));
        }

        return (int) $profileValue === (int) $targetValue;
    }

    /**
     * Vérifie si une valeur numérique du profil est dans la plage min/max du ciblage.
     */
    private function matchRangeCriterion(mixed $profileValue, mixed $targetValue): bool
    {
        $value = (int) $profileValue;
        $min   = $targetValue['min'] ?? null;
        $max   = $targetValue['max'] ?? null;

        if ($min !== null && $value < (int) $min) return false;
        if ($max !== null && $value > (int) $max) return false;

        return true;
    }
}
