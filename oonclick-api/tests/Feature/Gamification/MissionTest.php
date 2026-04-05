<?php

use App\Models\FeatureSetting;
use App\Models\UserMission;

describe('Missions', function () {

    beforeEach(function () {
        FeatureSetting::updateOrCreate(
            ['feature_slug' => 'missions'],
            [
                'label'      => 'Missions',
                'is_enabled' => true,
                'config'     => [
                    'missions' => [
                        ['slug' => 'checkin', 'title' => 'Check-in', 'type' => 'checkin', 'target' => 1, 'reward_fcfa' => 50, 'reward_xp' => 10],
                        ['slug' => 'watch_3', 'title' => 'Regarder 3 pubs', 'type' => 'views', 'target' => 3, 'reward_fcfa' => 100, 'reward_xp' => 20],
                    ],
                ],
            ]
        );
        FeatureSetting::updateOrCreate(
            ['feature_slug' => 'streak'],
            ['label' => 'Streak', 'is_enabled' => false, 'config' => []]
        );
    });

    it('retourne les missions du jour', function () {
        $subscriber = makeSubscriber();

        $response = $this->actingAs($subscriber)->getJson('/api/missions');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.slug', 'checkin');
        $response->assertJsonPath('data.0.completed', false);
    });

    it('reclame une mission completee', function () {
        $subscriber = makeSubscriber();

        // Creer une mission completee
        $mission = UserMission::create([
            'user_id'          => $subscriber->id,
            'mission_slug'     => 'checkin',
            'date'             => now()->toDateString(),
            'current_progress' => 1,
            'completed'        => true,
        ]);

        $response = $this->actingAs($subscriber)->postJson("/api/missions/{$mission->id}/claim");

        $response->assertStatus(200);
        $response->assertJsonPath('reward_fcfa', 50);
        $response->assertJsonPath('reward_xp', 10);

        $this->assertDatabaseHas('user_missions', [
            'id' => $mission->id,
        ]);
        expect(UserMission::find($mission->id)->rewarded_at)->not->toBeNull();
    });

    it('refuse de reclamer une mission non completee', function () {
        $subscriber = makeSubscriber();

        $mission = UserMission::create([
            'user_id'          => $subscriber->id,
            'mission_slug'     => 'watch_3',
            'date'             => now()->toDateString(),
            'current_progress' => 1,
            'completed'        => false,
        ]);

        $response = $this->actingAs($subscriber)->postJson("/api/missions/{$mission->id}/claim");

        $response->assertStatus(404);
    });

    it('retourne vide si feature desactivee', function () {
        FeatureSetting::where('feature_slug', 'missions')->update(['is_enabled' => false]);
        FeatureSetting::clearCache();

        $subscriber = makeSubscriber();
        $response = $this->actingAs($subscriber)->getJson('/api/missions');

        $response->assertStatus(200);
        $response->assertJsonPath('data', []);
    });
});
