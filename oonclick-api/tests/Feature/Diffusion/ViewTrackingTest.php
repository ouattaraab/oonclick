<?php

use App\Models\AdView;
use App\Models\Campaign;
use App\Models\Wallet;
use Illuminate\Support\Facades\Notification;

describe('Tracking des vues', function () {

    it('démarre une vue et retourne un ad_view_id', function () {
        $subscriber = makeSubscriber();
        $advertiser = makeAdvertiser();
        $campaign   = makeActiveCampaign($advertiser);

        $response = $this->actingAs($subscriber)
            ->postJson("/api/ads/{$campaign->id}/start");

        $response->assertStatus(201);
        $response->assertJsonStructure(['ad_view_id', 'started_at', 'expires_at']);

        $adViewId = $response->json('ad_view_id');
        $this->assertNotNull($adViewId);

        $this->assertDatabaseHas('ad_views', [
            'id'            => $adViewId,
            'campaign_id'   => $campaign->id,
            'subscriber_id' => $subscriber->id,
            'is_completed'  => false,
        ]);
    });

    it('crédite 60 FCFA après une vue complète', function () {
        Notification::fake();

        config([
            'oonclick.subscriber_earn'     => 60,
            'oonclick.cost_per_view'       => 100,
            'oonclick.min_watch_percent'   => 80,
            'oonclick.format_multipliers'  => ['video' => 1.0, 'scratch' => 1.5, 'quiz' => 1.3, 'flash' => 1.2],
        ]);

        $subscriber = makeSubscriber();
        $advertiser = makeAdvertiser();

        $campaign = Campaign::factory()->active()->create([
            'advertiser_id'    => $advertiser->id,
            'format'           => 'video',
            'duration_seconds' => 30,
            'views_count'      => 0,
            'max_views'        => 100,
            'budget'           => 10000,
            'cost_per_view'    => 100,
        ]);

        \App\Models\EscrowEntry::factory()->create([
            'campaign_id'   => $campaign->id,
            'amount_locked' => 10000,
            'status'        => 'locked',
        ]);

        // Démarrer la vue
        $startResponse = $this->actingAs($subscriber)
            ->postJson("/api/ads/{$campaign->id}/start");

        $startResponse->assertStatus(201);
        $adViewId = $startResponse->json('ad_view_id');

        // Compléter la vue avec 28s de visionnage (93% d'une vidéo de 30s)
        $completeResponse = $this->actingAs($subscriber)
            ->postJson("/api/ads/{$campaign->id}/complete", [
                'ad_view_id'            => $adViewId,
                'watch_duration_seconds' => 28,
            ]);

        $completeResponse->assertStatus(200);
        $completeResponse->assertJsonPath('credited', true);
        $completeResponse->assertJsonPath('amount', 60);

        // Vérifier le solde du wallet
        $wallet = Wallet::where('user_id', $subscriber->id)->first();
        expect($wallet->balance)->toBe(60);

        // Vérifier l'escrow
        $this->assertDatabaseHas('escrow_entries', [
            'campaign_id'     => $campaign->id,
            'amount_released' => 60,
        ]);
    });

    it('ne crédite pas si durée insuffisante', function () {
        Notification::fake();

        config([
            'oonclick.subscriber_earn'    => 60,
            'oonclick.min_watch_percent'  => 80,
            'oonclick.format_multipliers' => ['video' => 1.0],
        ]);

        $subscriber = makeSubscriber();
        $advertiser = makeAdvertiser();

        $campaign = Campaign::factory()->active()->create([
            'advertiser_id'    => $advertiser->id,
            'format'           => 'video',
            'duration_seconds' => 30,
            'views_count'      => 0,
            'max_views'        => 100,
            'budget'           => 10000,
            'cost_per_view'    => 100,
        ]);

        \App\Models\EscrowEntry::factory()->create([
            'campaign_id'   => $campaign->id,
            'amount_locked' => 10000,
            'status'        => 'locked',
        ]);

        $startResponse = $this->actingAs($subscriber)
            ->postJson("/api/ads/{$campaign->id}/start");

        $startResponse->assertStatus(201);
        $adViewId = $startResponse->json('ad_view_id');

        // Seulement 5 secondes regardées sur 30 (16% < 80%)
        $completeResponse = $this->actingAs($subscriber)
            ->postJson("/api/ads/{$campaign->id}/complete", [
                'ad_view_id'            => $adViewId,
                'watch_duration_seconds' => 5,
            ]);

        $completeResponse->assertStatus(200);
        $completeResponse->assertJsonPath('credited', false);

        $reason = $completeResponse->json('reason');
        $this->assertStringContainsStringIgnoringCase('durée', $reason);

        // Solde inchangé
        $wallet = Wallet::where('user_id', $subscriber->id)->first();
        expect($wallet->balance)->toBe(0);
    });

    it('refuse de compléter une vue déjà complétée', function () {
        $subscriber = makeSubscriber();
        $advertiser = makeAdvertiser();
        $campaign   = makeActiveCampaign($advertiser);

        // AdView déjà complétée
        $adView = AdView::factory()->completed()->create([
            'campaign_id'   => $campaign->id,
            'subscriber_id' => $subscriber->id,
        ]);

        $response = $this->actingAs($subscriber)
            ->postJson("/api/ads/{$campaign->id}/complete", [
                'ad_view_id'            => $adView->id,
                'watch_duration_seconds' => 30,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('credited', false);

        $reason = $response->json('reason');
        $this->assertStringContainsStringIgnoringCase('déjà', $reason);
    });

    it('bloque si limite horaire atteinte', function () {
        $subscriber = makeSubscriber();
        $advertiser = makeAdvertiser();
        $campaign   = makeActiveCampaign($advertiser);

        config(['oonclick.max_views_per_hour' => 10]);

        // Créer 10 AdViews dans la dernière heure pour cet abonné
        $otherCampaign = Campaign::factory()->active()->create([
            'advertiser_id' => $advertiser->id,
            'targeting'     => null,
        ]);

        AdView::factory()->count(10)->create([
            'subscriber_id' => $subscriber->id,
            'campaign_id'   => $otherCampaign->id,
            'started_at'    => now()->subMinutes(30),
        ]);

        $response = $this->actingAs($subscriber)
            ->postJson("/api/ads/{$campaign->id}/start");

        $response->assertStatus(403);

        $reason = $response->json('message');
        $this->assertStringContainsStringIgnoringCase('horaire', $reason);
    });

    it('bloque si campagne pleine', function () {
        $subscriber = makeSubscriber();
        $advertiser = makeAdvertiser();

        $campaign = Campaign::factory()->active()->create([
            'advertiser_id' => $advertiser->id,
            'views_count'   => 100,
            'max_views'     => 100,
            'targeting'     => null,
        ]);

        \App\Models\EscrowEntry::factory()->create([
            'campaign_id'   => $campaign->id,
            'amount_locked' => 10000,
            'status'        => 'locked',
        ]);

        $response = $this->actingAs($subscriber)
            ->postJson("/api/ads/{$campaign->id}/start");

        $response->assertStatus(403);

        $reason = $response->json('message');
        $this->assertStringContainsStringIgnoringCase('disponible', $reason);
    });

    it('refuse si non abonné', function () {
        $advertiser = makeAdvertiser();
        $campaign   = makeActiveCampaign($advertiser);

        $response = $this->actingAs($advertiser)
            ->postJson("/api/ads/{$campaign->id}/start");

        $response->assertStatus(403);
    });

    it('refuse de compléter une vue qui n\'appartient pas à l\'abonné', function () {
        $subscriber1 = makeSubscriber();
        $subscriber2 = makeSubscriber();
        $advertiser  = makeAdvertiser();
        $campaign    = makeActiveCampaign($advertiser);

        $adView = AdView::factory()->create([
            'campaign_id'   => $campaign->id,
            'subscriber_id' => $subscriber1->id,
        ]);

        $response = $this->actingAs($subscriber2)
            ->postJson("/api/ads/{$campaign->id}/complete", [
                'ad_view_id'            => $adView->id,
                'watch_duration_seconds' => 30,
            ]);

        $response->assertStatus(403);
    });

});
