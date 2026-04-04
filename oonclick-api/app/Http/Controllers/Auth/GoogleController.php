<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirige l'utilisateur vers Google pour l'authentification OAuth.
     * Le paramètre `role` est stocké en session pour savoir quel type de compte créer.
     */
    public function redirect(string $role = 'subscriber')
    {
        $allowedRoles = ['subscriber', 'advertiser'];
        $role = in_array($role, $allowedRoles) ? $role : 'subscriber';

        session(['google_auth_role' => $role]);

        return Socialite::driver('google')->redirect();
    }

    /**
     * Traite le callback de Google après autorisation.
     * Si l'utilisateur existe déjà → connexion.
     * Sinon → création du compte avec le rôle stocké en session.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('panel.login')
                ->withErrors(['google' => 'Impossible de se connecter avec Google. Veuillez réessayer.']);
        }

        // Chercher un utilisateur existant par email
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Utilisateur existant → connexion directe
            Auth::login($user, remember: true);

            return $this->redirectAfterLogin($user);
        }

        // Nouvel utilisateur → création
        $role = session('google_auth_role', 'subscriber');
        session()->forget('google_auth_role');

        $user = User::create([
            'name'      => $googleUser->getName(),
            'email'     => $googleUser->getEmail(),
            'password'  => bcrypt(Str::random(32)),
            'role'      => $role,
            'is_active' => true,
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        Auth::login($user, remember: true);

        return $this->redirectAfterLogin($user);
    }

    private function redirectAfterLogin(User $user)
    {
        return match ($user->role) {
            'admin'      => redirect()->route('panel.admin.dashboard'),
            'advertiser' => redirect()->route('panel.advertiser.dashboard'),
            default      => redirect('/'),
        };
    }
}
