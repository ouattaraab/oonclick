<?php

use App\Models\SubscriberProfile;

describe('POST /api/auth/complete-profile', function () {

    it('complète le profil abonné et génère un referral_code', function () {
        $user = makeSubscriber();

        // Supprimer le profil existant créé par makeSubscriber pour repartir de zéro
        SubscriberProfile::where('user_id', $user->id)->delete();

        $response = $this->actingAs($user)
            ->postJson('/api/auth/complete-profile', [
                'first_name'    => 'Kofi',
                'last_name'     => 'Atta',
                'gender'        => 'male',
                'date_of_birth' => '1995-06-15',
                'city'          => 'Abidjan',
                'operator'      => 'mtn',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Profil complété.');

        $profile = SubscriberProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($profile);
        $this->assertNotNull($profile->profile_completed_at);
        $this->assertNotNull($profile->referral_code);
        $this->assertEquals(8, strlen($profile->referral_code));
    });

    it('refuse si non abonné (advertiser)', function () {
        $advertiser = makeAdvertiser();

        $response = $this->actingAs($advertiser)
            ->postJson('/api/auth/complete-profile', [
                'first_name'    => 'John',
                'last_name'     => 'Doe',
                'gender'        => 'male',
                'date_of_birth' => '1990-01-01',
                'city'          => 'Abidjan',
                'operator'      => 'mtn',
            ]);

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Cette action est réservée aux subscribers.');
    });

    it('valide les champs obligatoires', function () {
        $user = makeSubscriber();

        // Supprimer le profil existant créé par makeSubscriber
        SubscriberProfile::where('user_id', $user->id)->delete();

        $response = $this->actingAs($user)
            ->postJson('/api/auth/complete-profile', [
                // first_name manquant
                'last_name'     => 'Atta',
                'gender'        => 'male',
                'date_of_birth' => '1995-06-15',
                'city'          => 'Abidjan',
                'operator'      => 'mtn',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrorFor('first_name');
    });

    it('valide le champ gender', function () {
        $user = makeSubscriber();
        SubscriberProfile::where('user_id', $user->id)->delete();

        $response = $this->actingAs($user)
            ->postJson('/api/auth/complete-profile', [
                'first_name'    => 'Kofi',
                'last_name'     => 'Atta',
                'gender'        => 'inconnu',
                'date_of_birth' => '1995-06-15',
                'city'          => 'Abidjan',
                'operator'      => 'mtn',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrorFor('gender');
    });

    it('valide le champ operator', function () {
        $user = makeSubscriber();
        SubscriberProfile::where('user_id', $user->id)->delete();

        $response = $this->actingAs($user)
            ->postJson('/api/auth/complete-profile', [
                'first_name'    => 'Kofi',
                'last_name'     => 'Atta',
                'gender'        => 'male',
                'date_of_birth' => '1995-06-15',
                'city'          => 'Abidjan',
                'operator'      => 'airtel',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrorFor('operator');
    });

    it('ne génère pas un nouveau referral_code si un existe déjà', function () {
        $user = makeSubscriber();

        $profile = SubscriberProfile::where('user_id', $user->id)->first();
        $existingCode = strtoupper('TESTCODE');
        $profile->referral_code = $existingCode;
        $profile->profile_completed_at = null;
        $profile->save();

        $response = $this->actingAs($user)
            ->postJson('/api/auth/complete-profile', [
                'first_name'    => 'Kofi',
                'last_name'     => 'Atta',
                'gender'        => 'male',
                'date_of_birth' => '1995-06-15',
                'city'          => 'Abidjan',
                'operator'      => 'mtn',
            ]);

        $response->assertStatus(200);

        $updatedProfile = SubscriberProfile::where('user_id', $user->id)->first();
        expect($updatedProfile->referral_code)->toBe($existingCode);
    });

    it('refuse si non authentifié', function () {
        $response = $this->postJson('/api/auth/complete-profile', [
            'first_name' => 'Kofi',
        ]);

        $response->assertStatus(401);
    });

});
