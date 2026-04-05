<?php

use App\Models\FeatureSetting;

describe('Offline Mode', function () {

    it('retourne 403 si feature desactivee', function () {
        FeatureSetting::updateOrCreate(
            ['feature_slug' => 'offline_mode'],
            ['label' => 'Offline', 'is_enabled' => false, 'config' => []]
        );

        $subscriber = makeSubscriber();

        $this->actingAs($subscriber)->getJson('/api/feed/preload')->assertStatus(403);
        $this->actingAs($subscriber)->postJson('/api/feed/sync', ['views' => []])->assertStatus(403);
    });

    it('preload retourne les campagnes avec validite', function () {
        FeatureSetting::updateOrCreate(
            ['feature_slug' => 'offline_mode'],
            ['label' => 'Offline', 'is_enabled' => true, 'config' => ['max_preload_campaigns' => 3, 'preload_validity_hours' => 24, 'sync_max_batch_size' => 10]]
        );

        $subscriber = makeSubscriber();
        $advertiser = makeAdvertiser();
        makeActiveCampaign($advertiser);

        $response = $this->actingAs($subscriber)->getJson('/api/feed/preload');

        $response->assertStatus(200);
        $response->assertJsonStructure(['preloaded_at', 'valid_until', 'campaigns']);
    });

    it('refuse si non authentifie', function () {
        $this->getJson('/api/feed/preload')->assertStatus(401);
    });
});
