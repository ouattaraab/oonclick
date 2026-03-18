<?php

use App\Models\AdView;
use App\Models\Campaign;
use App\Models\SubscriberProfile;

describe('GET /api/feed', function () {

    it('retourne les campagnes éligibles pour l\'abonné', function () {
        $advertiser = makeAdvertiser();
        $subscriber = makeSubscriber();

        // Assurer un profil complet pour l'abonné
        SubscriberProfile::where('user_id', $subscriber->id)->update([
            'city'                 => 'Abidjan',
            'operator'             => 'mtn',
            'profile_completed_at' => now(),
        ]);

        // Campagne active sans ciblage
        $campaign = Campaign::factory()->active()->create([
            'advertiser_id' => $advertiser->id,
            'targeting'     => null,
            'views_count'   => 0,
            'max_views'     => 100,
        ]);

        $response = $this->actingAs($subscriber)->getJson('/api/feed');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($campaign->id, $ids);
    });

    it('exclut les campagnes déjà vues', function () {
        $advertiser = makeAdvertiser();
        $subscriber = makeSubscriber();

        $campaign = Campaign::factory()->active()->create([
            'advertiser_id' => $advertiser->id,
            'targeting'     => null,
            'views_count'   => 0,
            'max_views'     => 100,
        ]);

        // Vue déjà complétée et créditée par cet abonné
        AdView::factory()->credited()->create([
            'campaign_id'   => $campaign->id,
            'subscriber_id' => $subscriber->id,
        ]);

        $response = $this->actingAs($subscriber)->getJson('/api/feed');

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertNotContains($campaign->id, $ids);
    });

    it('filtre par ciblage ville', function () {
        $advertiser = makeAdvertiser();
        $subscriber = makeSubscriber();

        // Abonné à Bouaké
        SubscriberProfile::where('user_id', $subscriber->id)->update([
            'city'                 => 'Bouaké',
            'profile_completed_at' => now(),
        ]);

        // Campagne ciblée uniquement sur Abidjan
        $campaign = Campaign::factory()->active()->withTargeting([
            'cities' => ['Abidjan'],
        ])->create([
            'advertiser_id' => $advertiser->id,
            'views_count'   => 0,
            'max_views'     => 100,
        ]);

        $response = $this->actingAs($subscriber)->getJson('/api/feed');

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertNotContains($campaign->id, $ids);
    });

    it('inclut une campagne ciblée qui matche', function () {
        $advertiser = makeAdvertiser();
        $subscriber = makeSubscriber();

        // Abonné à Abidjan avec MTN
        SubscriberProfile::where('user_id', $subscriber->id)->update([
            'city'                 => 'Abidjan',
            'operator'             => 'mtn',
            'profile_completed_at' => now(),
        ]);

        $campaign = Campaign::factory()->active()->withTargeting([
            'cities'    => ['Abidjan'],
            'operators' => ['mtn'],
        ])->create([
            'advertiser_id' => $advertiser->id,
            'views_count'   => 0,
            'max_views'     => 100,
        ]);

        $response = $this->actingAs($subscriber)->getJson('/api/feed');

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($campaign->id, $ids);
    });

    it('refuse si non abonné', function () {
        $advertiser = makeAdvertiser();

        $response = $this->actingAs($advertiser)->getJson('/api/feed');

        $response->assertStatus(403);
    });

    it('refuse si trust score insuffisant', function () {
        $subscriber = makeSubscriber(['trust_score' => 20]);

        $response = $this->actingAs($subscriber)->getJson('/api/feed');

        $response->assertStatus(403);
    });

    it('exclut les campagnes expirées', function () {
        $advertiser = makeAdvertiser();
        $subscriber = makeSubscriber();

        SubscriberProfile::where('user_id', $subscriber->id)->update([
            'profile_completed_at' => now(),
        ]);

        $campaign = Campaign::factory()->active()->create([
            'advertiser_id' => $advertiser->id,
            'targeting'     => null,
            'views_count'   => 0,
            'max_views'     => 100,
            'ends_at'       => now()->subDay(),
        ]);

        $response = $this->actingAs($subscriber)->getJson('/api/feed');

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertNotContains($campaign->id, $ids);
    });

    it('exclut les campagnes pleines (quota atteint)', function () {
        $advertiser = makeAdvertiser();
        $subscriber = makeSubscriber();

        SubscriberProfile::where('user_id', $subscriber->id)->update([
            'profile_completed_at' => now(),
        ]);

        $campaign = Campaign::factory()->active()->full()->create([
            'advertiser_id' => $advertiser->id,
            'targeting'     => null,
            'budget'        => 10000,
            'cost_per_view' => 100,
            'max_views'     => 100,
            'views_count'   => 100,
        ]);

        $response = $this->actingAs($subscriber)->getJson('/api/feed');

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertNotContains($campaign->id, $ids);
    });

});
