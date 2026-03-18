<?php

use App\Models\AdView;
use App\Models\Campaign;
use App\Models\SubscriberProfile;
use App\Models\User;
use App\Models\Wallet;
use App\Modules\Diffusion\Services\MatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Crée un abonné complet sans passer par les helpers Feature.
 * Les modèles sans HasFactory sont créés directement via Eloquent.
 */
function createSubscriberWithProfile(array $profileAttributes = []): User
{
    $user = User::factory()->create([
        'role'              => 'subscriber',
        'phone_verified_at' => now(),
        'is_active'         => true,
        'trust_score'       => 100,
    ]);

    Wallet::create([
        'user_id'         => $user->id,
        'balance'         => 0,
        'pending_balance' => 0,
        'total_earned'    => 0,
        'total_withdrawn' => 0,
    ]);

    SubscriberProfile::create(array_merge([
        'user_id'              => $user->id,
        'first_name'           => 'Test',
        'last_name'            => 'User',
        'city'                 => 'Abidjan',
        'country'              => 'CI',
        'operator'             => 'mtn',
        'gender'               => 'male',
        'date_of_birth'        => '1995-06-15',
        'interests'            => ['sport', 'tech'],
        'referral_code'        => strtoupper(str()->random(8)),
        'profile_completed_at' => now(),
    ], $profileAttributes));

    return $user->load('profile');
}

/**
 * Crée une campagne active sans passer par la factory du modèle.
 */
function createActiveCampaignForMatching(int $advertiserId, array $attrs = []): Campaign
{
    return Campaign::create(array_merge([
        'advertiser_id'    => $advertiserId,
        'title'            => 'Campagne Test',
        'format'           => 'video',
        'status'           => 'active',
        'budget'           => 10000,
        'cost_per_view'    => 100,
        'max_views'        => 100,
        'views_count'      => 0,
        'media_url'        => 'https://cdn.test/video.mp4',
        'duration_seconds' => 30,
        'targeting'        => null,
    ], $attrs));
}

describe('MatchingService', function () {

    it('retourne les campagnes sans ciblage pour tous', function () {
        $subscriber = createSubscriberWithProfile();
        $advertiser = User::factory()->create(['role' => 'advertiser', 'phone_verified_at' => now()]);

        $campaign = createActiveCampaignForMatching($advertiser->id, ['targeting' => null]);

        $service   = new MatchingService();
        $campaigns = $service->getEligibleCampaigns($subscriber);

        $ids = $campaigns->pluck('id')->toArray();
        expect($ids)->toContain($campaign->id);
    });

    it('filtre les campagnes déjà vues', function () {
        $subscriber = createSubscriberWithProfile();
        $advertiser = User::factory()->create(['role' => 'advertiser', 'phone_verified_at' => now()]);

        $campaign = createActiveCampaignForMatching($advertiser->id);

        // Vue complétée par cet abonné
        AdView::create([
            'campaign_id'            => $campaign->id,
            'subscriber_id'          => $subscriber->id,
            'started_at'             => now()->subMinutes(5),
            'completed_at'           => now()->subMinutes(4),
            'watch_duration_seconds' => 28,
            'is_completed'           => true,
            'is_credited'            => true,
            'credited_at'            => now()->subMinutes(4),
            'amount_credited'        => 60,
            'ip_address'             => '127.0.0.1',
            'user_agent'             => 'Test',
        ]);

        $service   = new MatchingService();
        $campaigns = $service->getEligibleCampaigns($subscriber);

        $ids = $campaigns->pluck('id')->toArray();
        expect($ids)->not->toContain($campaign->id);
    });

    it('exclut les campagnes expirées', function () {
        $subscriber = createSubscriberWithProfile();
        $advertiser = User::factory()->create(['role' => 'advertiser', 'phone_verified_at' => now()]);

        $campaign = createActiveCampaignForMatching($advertiser->id, [
            'ends_at' => now()->subDay(),
        ]);

        $service   = new MatchingService();
        $campaigns = $service->getEligibleCampaigns($subscriber);

        $ids = $campaigns->pluck('id')->toArray();
        expect($ids)->not->toContain($campaign->id);
    });

    it('exclut les campagnes pleines', function () {
        $subscriber = createSubscriberWithProfile();
        $advertiser = User::factory()->create(['role' => 'advertiser', 'phone_verified_at' => now()]);

        $campaign = createActiveCampaignForMatching($advertiser->id, [
            'views_count' => 100,
            'max_views'   => 100,
        ]);

        $service   = new MatchingService();
        $campaigns = $service->getEligibleCampaigns($subscriber);

        $ids = $campaigns->pluck('id')->toArray();
        expect($ids)->not->toContain($campaign->id);
    });

    it('respecte le ciblage par ville', function () {
        $subscriber = createSubscriberWithProfile(['city' => 'Abidjan']);
        $advertiser = User::factory()->create(['role' => 'advertiser', 'phone_verified_at' => now()]);

        $campaign = createActiveCampaignForMatching($advertiser->id, [
            'targeting' => ['cities' => ['Bouaké']],
        ]);

        $service   = new MatchingService();
        $campaigns = $service->getEligibleCampaigns($subscriber);

        $ids = $campaigns->pluck('id')->toArray();
        expect($ids)->not->toContain($campaign->id);
    });

    it('respecte le ciblage par opérateur', function () {
        $subscriber = createSubscriberWithProfile(['operator' => 'mtn']);
        $advertiser = User::factory()->create(['role' => 'advertiser', 'phone_verified_at' => now()]);

        $campaign = createActiveCampaignForMatching($advertiser->id, [
            'targeting' => ['operators' => ['moov']],
        ]);

        $service   = new MatchingService();
        $campaigns = $service->getEligibleCampaigns($subscriber);

        $ids = $campaigns->pluck('id')->toArray();
        expect($ids)->not->toContain($campaign->id);
    });

    it('respecte le ciblage par âge', function () {
        // Abonné de 17 ans
        $subscriber = createSubscriberWithProfile([
            'date_of_birth' => now()->subYears(17)->subDays(10)->format('Y-m-d'),
        ]);

        $advertiser = User::factory()->create(['role' => 'advertiser', 'phone_verified_at' => now()]);

        $campaign = createActiveCampaignForMatching($advertiser->id, [
            'targeting' => ['age_min' => 18],
        ]);

        $service   = new MatchingService();
        $campaigns = $service->getEligibleCampaigns($subscriber);

        $ids = $campaigns->pluck('id')->toArray();
        expect($ids)->not->toContain($campaign->id);
    });

    it('inclut les campagnes qui matchent le ciblage', function () {
        $subscriber = createSubscriberWithProfile([
            'city'     => 'Abidjan',
            'operator' => 'mtn',
        ]);

        $advertiser = User::factory()->create(['role' => 'advertiser', 'phone_verified_at' => now()]);

        $campaign = createActiveCampaignForMatching($advertiser->id, [
            'targeting' => [
                'cities'    => ['Abidjan'],
                'operators' => ['mtn'],
            ],
        ]);

        $service   = new MatchingService();
        $campaigns = $service->getEligibleCampaigns($subscriber);

        $ids = $campaigns->pluck('id')->toArray();
        expect($ids)->toContain($campaign->id);
    });

    it('limite le feed à 10 campagnes maximum', function () {
        $subscriber = createSubscriberWithProfile();
        $advertiser = User::factory()->create(['role' => 'advertiser', 'phone_verified_at' => now()]);

        for ($i = 0; $i < 15; $i++) {
            createActiveCampaignForMatching($advertiser->id, [
                'targeting'   => null,
                'views_count' => 0,
                'max_views'   => 100,
            ]);
        }

        $service   = new MatchingService();
        $campaigns = $service->getEligibleCampaigns($subscriber);

        expect($campaigns->count())->toBeLessThanOrEqual(10);
    });

    it('calcule correctement l\'âge à partir de la date de naissance', function () {
        $service = new MatchingService();

        $age = $service->calculateAge('1995-06-15');
        expect($age)->toBeInt();
        expect($age)->toBeGreaterThan(0);

        $ageNull = $service->calculateAge(null);
        expect($ageNull)->toBeNull();
    });

    it('respecte le ciblage par âge maximum', function () {
        // Abonné de 35 ans
        $subscriber = createSubscriberWithProfile([
            'date_of_birth' => now()->subYears(35)->format('Y-m-d'),
        ]);

        $advertiser = User::factory()->create(['role' => 'advertiser', 'phone_verified_at' => now()]);

        // Campagne pour les moins de 30 ans
        $campaign = createActiveCampaignForMatching($advertiser->id, [
            'targeting' => ['age_max' => 30],
        ]);

        $service   = new MatchingService();
        $campaigns = $service->getEligibleCampaigns($subscriber);

        $ids = $campaigns->pluck('id')->toArray();
        expect($ids)->not->toContain($campaign->id);
    });

    it('respecte le ciblage par centres d\'intérêt', function () {
        $subscriber = createSubscriberWithProfile([
            'interests' => ['sport', 'tech'],
        ]);

        $advertiser = User::factory()->create(['role' => 'advertiser', 'phone_verified_at' => now()]);

        // Campagne ciblant un intérêt que l'abonné n'a pas
        $campaign = createActiveCampaignForMatching($advertiser->id, [
            'targeting' => ['interests' => ['cuisine', 'voyage']],
        ]);

        $service   = new MatchingService();
        $campaigns = $service->getEligibleCampaigns($subscriber);

        $ids = $campaigns->pluck('id')->toArray();
        expect($ids)->not->toContain($campaign->id);
    });

});
