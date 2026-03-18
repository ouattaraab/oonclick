<?php

use App\Models\FraudEvent;
use App\Models\User;
use App\Modules\Fraud\Services\TrustScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Crée un FraudEvent directement via Eloquent (le modèle n'a pas HasFactory dans le scope Unit).
 */
function createFraudEvent(int $userId, array $attrs = []): FraudEvent
{
    return FraudEvent::create(array_merge([
        'user_id'            => $userId,
        'type'               => 'rapid_views',
        'severity'           => 'medium',
        'description'        => 'Test fraud event',
        'metadata'           => ['test' => true],
        'trust_score_impact' => -10,
        'is_resolved'        => false,
        'resolved_at'        => null,
        'resolved_by'        => null,
    ], $attrs));
}

describe('TrustScoreService', function () {

    it('recalcule le score en appliquant les pénalités actives', function () {
        $user = User::factory()->create(['trust_score' => 100]);

        // 2 FraudEvents non résolus : -10 et -20
        createFraudEvent($user->id, ['trust_score_impact' => -10, 'is_resolved' => false]);
        createFraudEvent($user->id, ['trust_score_impact' => -20, 'is_resolved' => false]);

        $service = new TrustScoreService();
        $score   = $service->recalculate($user->id);

        expect($score)->toBe(70);

        $this->assertDatabaseHas('users', [
            'id'          => $user->id,
            'trust_score' => 70,
        ]);
    });

    it('borne le score à 0 minimum', function () {
        $user = User::factory()->create([
            'trust_score'  => 100,
            'is_suspended' => false,
        ]);

        // 3 FraudEvents critiques (-40 chacun) → 100 - 120 = -20 → borné à 0
        createFraudEvent($user->id, ['trust_score_impact' => -40, 'is_resolved' => false]);
        createFraudEvent($user->id, ['trust_score_impact' => -40, 'is_resolved' => false]);
        createFraudEvent($user->id, ['trust_score_impact' => -40, 'is_resolved' => false]);

        $service = new TrustScoreService();
        $score   = $service->recalculate($user->id);

        expect($score)->toBe(0);

        $this->assertDatabaseHas('users', [
            'id'          => $user->id,
            'trust_score' => 0,
        ]);
    });

    it('suspend automatiquement l\'utilisateur quand le score atteint 0 via applyPenalty', function () {
        $user = User::factory()->create([
            'trust_score'  => 100,
            'is_suspended' => false,
        ]);

        // Pour faire tomber le score recalculé (base 100) à 0, on cumule -40 × 3 = -120 >= 100
        // D'abord on crée 2 pénalités existantes
        createFraudEvent($user->id, ['trust_score_impact' => -40, 'is_resolved' => false]);
        createFraudEvent($user->id, ['trust_score_impact' => -40, 'is_resolved' => false]);

        // Puis on applique la pénalité finale via applyPenalty
        $service = new TrustScoreService();
        $service->applyPenalty($user->id, 'rapid_views', 'critical', 'Test suspension automatique');

        // score = 100 - 40 - 40 - 40 = -20 → borné à 0
        $updatedUser = $user->fresh();
        expect($updatedUser->trust_score)->toBe(0);
        expect($updatedUser->is_suspended)->toBeTrue();
    });

    it('ignore les événements résolus', function () {
        $user = User::factory()->create(['trust_score' => 100]);

        // 1 FraudEvent résolu (-20) + 1 non résolu (-10)
        createFraudEvent($user->id, [
            'trust_score_impact' => -20,
            'is_resolved'        => true,
            'resolved_at'        => now(),
        ]);

        createFraudEvent($user->id, [
            'trust_score_impact' => -10,
            'is_resolved'        => false,
        ]);

        $service = new TrustScoreService();
        $score   = $service->recalculate($user->id);

        // Seul -10 est appliqué (l'événement résolu est ignoré)
        expect($score)->toBe(90);

        $this->assertDatabaseHas('users', [
            'id'          => $user->id,
            'trust_score' => 90,
        ]);
    });

    it('applique une pénalité et recalcule', function () {
        $user = User::factory()->create(['trust_score' => 100]);

        $service = new TrustScoreService();
        $event   = $service->applyPenalty(
            $user->id,
            'rapid_views',
            'medium',
            'Vues trop rapides détectées',
            ['ad_view_id' => 42]
        );

        // rapid_views + medium = -10
        expect($event)->toBeInstanceOf(FraudEvent::class);

        $this->assertDatabaseHas('fraud_events', [
            'user_id'            => $user->id,
            'type'               => 'rapid_views',
            'severity'           => 'medium',
            'trust_score_impact' => -10,
            'is_resolved'        => false,
        ]);

        $this->assertDatabaseHas('users', [
            'id'          => $user->id,
            'trust_score' => 90,
        ]);
    });

    it('résout un événement et recalcule', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create(['trust_score' => 80]);

        $event = createFraudEvent($user->id, [
            'trust_score_impact' => -20,
            'is_resolved'        => false,
        ]);

        $service       = new TrustScoreService();
        $resolvedEvent = $service->resolve($event->id, $admin->id);

        expect($resolvedEvent->is_resolved)->toBeTrue();
        expect($resolvedEvent->resolved_at)->not->toBeNull();

        // Après résolution, le score doit remonter à 100
        $this->assertDatabaseHas('users', [
            'id'          => $user->id,
            'trust_score' => 100,
        ]);
    });

    it('lève la suspension si le score redevient positif après résolution', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create([
            'trust_score'       => 0,
            'is_suspended'      => true,
            'suspension_reason' => 'Score épuisé',
        ]);

        $event = createFraudEvent($user->id, [
            'trust_score_impact' => -100,
            'severity'           => 'critical',
            'is_resolved'        => false,
        ]);

        $service = new TrustScoreService();
        $service->resolve($event->id, $admin->id);

        $updatedUser = $user->fresh();
        expect($updatedUser->is_suspended)->toBeFalse();
        expect($updatedUser->trust_score)->toBeGreaterThan(0);
    });

    it('retourne 100 si aucun événement de fraude', function () {
        $user = User::factory()->create(['trust_score' => 75]);

        $service = new TrustScoreService();
        $score   = $service->recalculate($user->id);

        expect($score)->toBe(100);
    });

});
