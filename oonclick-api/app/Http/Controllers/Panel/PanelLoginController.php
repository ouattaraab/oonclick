<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PanelLoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectAfterLogin(Auth::user());
        }

        return view('panel.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        // Support login via email or phone
        $field = filter_var($credentials['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (Auth::attempt([$field => $credentials['email'], 'password' => $credentials['password']], $remember)) {
            $request->session()->regenerate();

            return $this->redirectAfterLogin(Auth::user());
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => 'Ces identifiants ne correspondent à aucun compte.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function redirectAfterLogin($user)
    {
        return match ($user->role) {
            'admin'      => redirect('/panel/admin'),
            'advertiser' => redirect('/panel/advertiser'),
            'subscriber' => redirect('/'),
            default      => redirect('/panel/admin'),
        };
    }
}
