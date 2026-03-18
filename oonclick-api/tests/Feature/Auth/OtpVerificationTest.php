<?php

use App\Models\OtpCode;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

describe('POST /api/auth/verify-otp', function () {

    it('vérifie un OTP valide et retourne un token Sanctum', function () {
        Notification::fake();

        $user = User::factory()->create([
            'phone'             => '+22507' . rand(10000000, 99999999),
            'role'              => 'subscriber',
            'phone_verified_at' => null,
            'is_active'         => false,
        ]);

        OtpCode::factory()->create([
            'phone'      => $user->phone,
            'code'       => Hash::make('123456'),
            'type'       => 'registration',
            'expires_at' => now()->addMinutes(10),
            'used_at'    => null,
            'attempts'   => 0,
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => $user->phone,
            'code'  => '123456',
            'type'  => 'registration',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'user', 'wallet']);
        $this->assertNotNull($response->json('token'));

        $this->assertDatabaseHas('users', [
            'id'       => $user->id,
            'is_active' => true,
        ]);

        $updatedUser = $user->fresh();
        $this->assertNotNull($updatedUser->phone_verified_at);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
        ]);
    });

    it('crédite le bonus inscription de 500 FCFA', function () {
        Notification::fake();

        $user = User::factory()->create([
            'phone'             => '+22507' . rand(10000000, 99999999),
            'role'              => 'subscriber',
            'phone_verified_at' => null,
            'is_active'         => false,
        ]);

        OtpCode::factory()->create([
            'phone'      => $user->phone,
            'code'       => Hash::make('123456'),
            'type'       => 'registration',
            'expires_at' => now()->addMinutes(10),
            'used_at'    => null,
            'attempts'   => 0,
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => $user->phone,
            'code'  => '123456',
            'type'  => 'registration',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('wallet.balance', 500);

        $wallet = Wallet::where('user_id', $user->id)->first();
        expect($wallet->balance)->toBe(500);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id'   => $wallet->id,
            'type'        => 'bonus',
            'amount'      => 500,
            'reference'   => "SIGNUP_BONUS_{$user->id}",
        ]);
    });

    it('rejette un OTP expiré', function () {
        $user = User::factory()->create([
            'phone'             => '+22507' . rand(10000000, 99999999),
            'role'              => 'subscriber',
            'phone_verified_at' => null,
        ]);

        OtpCode::factory()->expired()->create([
            'phone' => $user->phone,
            'code'  => Hash::make('123456'),
            'type'  => 'registration',
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => $user->phone,
            'code'  => '123456',
            'type'  => 'registration',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Code invalide ou expiré.');
    });

    it('rejette un code incorrect', function () {
        $user = User::factory()->create([
            'phone'             => '+22507' . rand(10000000, 99999999),
            'role'              => 'subscriber',
            'phone_verified_at' => null,
        ]);

        OtpCode::factory()->create([
            'phone'      => $user->phone,
            'code'       => Hash::make('123456'),
            'type'       => 'registration',
            'expires_at' => now()->addMinutes(10),
            'used_at'    => null,
            'attempts'   => 0,
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => $user->phone,
            'code'  => '000000',
            'type'  => 'registration',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Code invalide ou expiré.');
    });

    it('bloque après 3 tentatives', function () {
        $user = User::factory()->create([
            'phone'             => '+22507' . rand(10000000, 99999999),
            'role'              => 'subscriber',
            'phone_verified_at' => null,
        ]);

        // OtpCode avec attempts=3 (déjà au maximum de tentatives)
        OtpCode::factory()->create([
            'phone'      => $user->phone,
            'code'       => Hash::make('123456'),
            'type'       => 'registration',
            'expires_at' => now()->addMinutes(10),
            'used_at'    => null,
            'attempts'   => 3,
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => $user->phone,
            'code'  => '999999',
            'type'  => 'registration',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Code invalide ou expiré.');
    });

    it("n'accorde pas de bonus deux fois (idempotence)", function () {
        Notification::fake();

        $user = User::factory()->create([
            'phone'             => '+22507' . rand(10000000, 99999999),
            'role'              => 'subscriber',
            'phone_verified_at' => null,
            'is_active'         => false,
        ]);

        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        // Simuler un bonus déjà crédité
        WalletTransaction::factory()->create([
            'wallet_id'   => $wallet->id,
            'type'        => 'bonus',
            'amount'      => 500,
            'reference'   => "SIGNUP_BONUS_{$user->id}",
            'status'      => 'completed',
            'balance_after' => 500,
        ]);
        $wallet->balance = 500;
        $wallet->save();

        OtpCode::factory()->create([
            'phone'      => $user->phone,
            'code'       => Hash::make('123456'),
            'type'       => 'registration',
            'expires_at' => now()->addMinutes(10),
            'used_at'    => null,
            'attempts'   => 0,
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'phone' => $user->phone,
            'code'  => '123456',
            'type'  => 'registration',
        ]);

        $response->assertStatus(200);

        // Le solde doit rester à 500, pas 1000
        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 500,
        ]);
    });

});
