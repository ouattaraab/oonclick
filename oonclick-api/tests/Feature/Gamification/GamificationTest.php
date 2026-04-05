<?php

describe('Gamification', function () {

    it('retourne le profil gamification', function () {
        $subscriber = makeSubscriber();

        $response = $this->actingAs($subscriber)->getJson('/api/gamification/profile');

        $response->assertStatus(200);
        $response->assertJsonStructure(['xp', 'level', 'next_level', 'xp_for_next', 'progress_percent', 'badges']);
        $response->assertJsonPath('level', 1);
        $response->assertJsonPath('xp', 0);
    });

    it('retourne le leaderboard', function () {
        $subscriber = makeSubscriber();
        makeSubscriber(); // second user

        $response = $this->actingAs($subscriber)->getJson('/api/gamification/leaderboard');

        $response->assertStatus(200);
        $response->assertJsonStructure(['leaderboard' => [['rank', 'user_id', 'name', 'xp', 'score', 'level']]]);
    });

    it('retourne les badges', function () {
        $subscriber = makeSubscriber();

        $response = $this->actingAs($subscriber)->getJson('/api/gamification/badges');

        $response->assertStatus(200);
    });
});
