<?php

use App\Models\OtpCode;
use App\Models\User;
use App\Modules\Auth\Services\SmsService;
use Illuminate\Support\Facades\Http;

describe('POST /api/auth/register', function () {

    it('enregistre un nouvel abonné et envoie un OTP', function () {
        // Mock SmsService pour ne pas envoyer de vrai SMS
        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('sendOtp')
                ->once()
                ->andReturn(true);
        });

        $phone = '+22507' . rand(10000000, 99999999);

        $response = $this->postJson('/api/auth/register', [
            'phone' => $phone,
            'role'  => 'subscriber',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Code envoyé.');
        $response->assertJsonPath('phone', $phone);

        $this->assertDatabaseHas('users', [
            'phone' => $phone,
            'role'  => 'subscriber',
        ]);

        $this->assertDatabaseHas('otp_codes', [
            'phone' => $phone,
            'type'  => 'registration',
        ]);
    });

    it('refuse si le phone est déjà vérifié', function () {
        $user = User::factory()->create([
            'phone'             => '+2250700000001',
            'phone_verified_at' => now(),
            'role'              => 'subscriber',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'phone' => '+2250700000001',
            'role'  => 'subscriber',
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('message', 'Compte existant. Veuillez vous connecter.');
    });

    it('rejette un phone invalide', function () {
        $response = $this->postJson('/api/auth/register', [
            'phone' => 'pas_un_phone',
            'role'  => 'subscriber',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrorFor('phone');
    });

    it('rejette si role manquant', function () {
        $response = $this->postJson('/api/auth/register', [
            'phone' => '+22507' . rand(10000000, 99999999),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrorFor('role');
    });

    it('réutilise un compte non vérifié existant', function () {
        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('sendOtp')->once()->andReturn(true);
        });

        $phone = '+2250799888777';

        User::factory()->create([
            'phone'             => $phone,
            'role'              => 'subscriber',
            'phone_verified_at' => null,
        ]);

        $response = $this->postJson('/api/auth/register', [
            'phone' => $phone,
            'role'  => 'subscriber',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseCount('users', 1);
    });

});
