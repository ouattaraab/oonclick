<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserConsent;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function showSubscriberForm()
    {
        if (Auth::check()) {
            return $this->redirectAuthenticatedUser(Auth::user());
        }

        return view('auth.register');
    }

    public function showAdvertiserForm()
    {
        if (Auth::check()) {
            return $this->redirectAuthenticatedUser(Auth::user());
        }

        return view('auth.register-advertiser');
    }

    public function registerSubscriber(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'phone'    => 'nullable|string|unique:users,phone',
            'email'    => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required'      => 'Le nom complet est obligatoire.',
            'phone.unique'       => 'Ce numero de telephone est deja utilise.',
            'email.email'        => 'Veuillez saisir une adresse email valide.',
            'email.unique'       => 'Cette adresse email est deja utilisee.',
            'password.min'       => 'Le mot de passe doit contenir au moins 8 caracteres.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ]);

        // Au moins un identifiant (email ou téléphone) est requis
        if (empty($data['email']) && empty($data['phone'])) {
            return back()->withInput()->withErrors([
                'email' => 'Veuillez renseigner au moins un email ou un numero de telephone.',
            ]);
        }

        $user = User::create([
            'name'      => $data['name'],
            'phone'     => $data['phone'] ?: null,
            'email'     => $data['email'] ?: null,
            'password'  => Hash::make($data['password']),
            'role'      => 'subscriber',
            'is_active' => true,
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        // Create subscriber profile so the user appears in audience targeting
        \App\Models\SubscriberProfile::create([
            'user_id'              => $user->id,
            'profile_completed_at' => now(),
        ]);

        // Record mandatory consents (C1-C4)
        $ip = $request->ip();
        $ua = $request->userAgent();
        UserConsent::record($user->id, 'C1', true, $ip, $ua); // CGU + Privacy Policy
        UserConsent::record($user->id, 'C2', true, $ip, $ua); // Targeted advertising
        UserConsent::record($user->id, 'C3', true, $ip, $ua); // International data transfer
        UserConsent::record($user->id, 'C4', true, $ip, $ua); // Device fingerprinting

        // Record optional consents
        UserConsent::record($user->id, 'C5', (bool) $request->input('consent_notifications'), $ip, $ua);
        UserConsent::record($user->id, 'C6', (bool) $request->input('consent_marketing'), $ip, $ua);

        Auth::login($user);

        return redirect()->route('home')
            ->with('success', 'Compte créé avec succès !');
    }

    public function registerAdvertiser(Request $request)
    {
        $data = $request->validate([
            // Step 1 — credentials
            'name'            => 'required|string|max:255',
            'email'           => 'nullable|email|unique:users,email',
            'phone'           => 'nullable|string|unique:users,phone',
            'password'        => 'required|string|min:8|confirmed',
            'advertiser_type' => 'required|in:individual,company',

            // Step 2 — individual fields
            'birth_date'      => 'nullable|date',
            'city'            => 'nullable|string|max:100',
            'id_number'       => 'nullable|string|max:50',

            // Step 2 — company fields
            'company'         => 'nullable|string|max:255',
            'sector'          => 'nullable|string|max:100',
            'rccm'            => 'nullable|string|max:50',
            'nif'             => 'nullable|string|max:50',
            'website'         => 'nullable|url|max:255',
            'address'         => 'nullable|string|max:255',
            'company_size'    => 'nullable|string|max:20',

            // Step 3 — campaign preferences (optional)
            'monthly_budget'  => 'nullable|string|max:50',
            'target_sectors'  => 'nullable|array',
        ], [
            'name.required'            => 'Le nom complet est obligatoire.',
            'email.email'              => 'Veuillez saisir une adresse email valide.',
            'email.unique'             => 'Cette adresse email est deja utilisee.',
            'phone.unique'             => 'Ce numero de telephone est deja utilise.',
            'password.min'             => 'Le mot de passe doit contenir au moins 8 caracteres.',
            'password.confirmed'       => 'Les mots de passe ne correspondent pas.',
            'advertiser_type.required' => 'Veuillez choisir un type de compte annonceur.',
            'advertiser_type.in'       => 'Le type de compte annonceur est invalide.',
        ]);

        // Au moins un identifiant (email ou telephone) est requis
        if (empty($data['email']) && empty($data['phone'])) {
            return back()->withInput()->withErrors([
                'email' => 'Veuillez renseigner au moins un email ou un numero de telephone.',
            ]);
        }

        $user = User::create([
            'name'            => $data['name'],
            'phone'           => $data['phone'] ?: null,
            'email'           => $data['email'] ?: null,
            'password'        => Hash::make($data['password']),
            'role'            => 'advertiser',
            'is_active'       => true,
            'advertiser_type' => $data['advertiser_type'],

            // Individual
            'birth_date'      => $data['birth_date'] ?? null,
            'city'            => $data['city'] ?? null,
            'id_number'       => $data['id_number'] ?? null,

            // Company
            'company'         => $data['company'] ?? null,
            'sector'          => $data['sector'] ?? null,
            'rccm'            => $data['rccm'] ?? null,
            'nif'             => $data['nif'] ?? null,
            'website'         => $data['website'] ?? null,
            'address'         => $data['address'] ?? null,
            'company_size'    => $data['company_size'] ?? null,

            // Campaign preferences
            'monthly_budget'  => $data['monthly_budget'] ?? null,
            'target_sectors'  => $data['target_sectors'] ?? null,
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        // Record mandatory consents (C1-C4)
        $ip = $request->ip();
        $ua = $request->userAgent();
        UserConsent::record($user->id, 'C1', true, $ip, $ua); // CGU + Privacy Policy
        UserConsent::record($user->id, 'C2', true, $ip, $ua); // Targeted advertising / data usage
        UserConsent::record($user->id, 'C3', true, $ip, $ua); // International data transfer
        UserConsent::record($user->id, 'C4', true, $ip, $ua); // Device fingerprinting

        Auth::login($user);

        return redirect()->route('panel.advertiser.dashboard');
    }

    private function redirectAuthenticatedUser($user)
    {
        return match ($user->role) {
            'admin'      => redirect()->route('panel.admin.dashboard'),
            'advertiser' => redirect()->route('panel.advertiser.dashboard'),
            default      => redirect('/'),
        };
    }
}
