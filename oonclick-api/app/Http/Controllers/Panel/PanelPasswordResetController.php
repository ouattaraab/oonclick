<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class PanelPasswordResetController extends Controller
{
    /**
     * Affiche le formulaire de demande de réinitialisation de mot de passe.
     *
     * GET /panel/forgot-password
     */
    public function showForgotForm()
    {
        return view('panel.forgot-password');
    }

    /**
     * Génère un code à 6 chiffres et l'envoie par email à l'annonceur.
     *
     * POST /panel/forgot-password
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'L\'adresse email est requise.',
            'email.email'    => 'L\'adresse email n\'est pas valide.',
        ]);

        $email = $request->input('email');

        // Vérifier que l'email existe dans la table users
        $user = User::where('email', $email)->first();

        if (! $user) {
            // Message générique pour ne pas révéler l'existence du compte
            return back()->with('status', 'Si cet email est enregistré, vous recevrez un code de réinitialisation.');
        }

        // Générer un code à 6 chiffres
        $code = (string) random_int(100000, 999999);

        // Stocker le code dans password_reset_tokens (remplace tout token existant)
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token'      => Hash::make($code),
                'created_at' => now()->toDateTimeString(),
            ]
        );

        // Stocker l'email en session pour pré-remplir le formulaire de saisie du code
        session(['password_reset_email' => $email]);

        // Envoyer le code par email
        Mail::send([], [], function (Message $message) use ($email, $user, $code) {
            $name = $user->name ?? 'Annonceur';

            $html  = "<!DOCTYPE html><html><body style='font-family:Inter,sans-serif;background:#f8fafc;padding:40px;'>";
            $html .= "<div style='max-width:480px;margin:auto;background:#fff;border-radius:16px;padding:40px;box-shadow:0 8px 32px rgba(0,0,0,0.1);'>";
            $html .= "<div style='text-align:center;margin-bottom:32px;'>";
            $html .= "<span style='font-size:28px;font-weight:900;color:#0F172A;letter-spacing:-1px;'><span style='color:#0EA5E9;'>oon</span>.click</span>";
            $html .= "</div>";
            $html .= "<h2 style='font-size:18px;font-weight:700;color:#0F172A;margin-bottom:8px;'>Réinitialisation de mot de passe</h2>";
            $html .= "<p style='color:#64748B;font-size:14px;margin-bottom:24px;'>Bonjour {$name},</p>";
            $html .= "<p style='color:#374151;font-size:14px;margin-bottom:24px;'>Voici votre code de réinitialisation de mot de passe. Ce code expire dans <strong>15 minutes</strong>.</p>";
            $html .= "<div style='text-align:center;background:#F0F9FF;border:2px solid #BAE6FD;border-radius:12px;padding:24px;margin-bottom:24px;'>";
            $html .= "<span style='font-size:36px;font-weight:900;letter-spacing:8px;color:#0EA5E9;'>{$code}</span>";
            $html .= "</div>";
            $html .= "<p style='color:#94A3B8;font-size:12px;'>Si vous n\'avez pas demandé cette réinitialisation, ignorez cet email.</p>";
            $html .= "</div></body></html>";

            $message
                ->to($email)
                ->subject('Code de réinitialisation — oon.click')
                ->html($html);
        });

        return back()->with('status', 'Si cet email est enregistré, vous recevrez un code de réinitialisation.');
    }

    /**
     * Affiche le formulaire de saisie du code et du nouveau mot de passe.
     *
     * GET /panel/reset-password
     */
    public function showResetForm(Request $request)
    {
        return view('panel.reset-password', [
            'email' => $request->query('email', ''),
        ]);
    }

    /**
     * Valide le code et met à jour le mot de passe de l'utilisateur.
     *
     * POST /panel/reset-password
     */
    public function reset(Request $request)
    {
        $request->validate([
            'email'                 => ['required', 'email'],
            'code'                  => ['required', 'string', 'size:6'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required'        => 'L\'adresse email est requise.',
            'email.email'           => 'L\'adresse email n\'est pas valide.',
            'code.required'         => 'Le code de vérification est requis.',
            'code.size'             => 'Le code doit comporter exactement 6 chiffres.',
            'password.required'     => 'Le nouveau mot de passe est requis.',
            'password.min'          => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed'    => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        $email = $request->input('email');
        $code  = $request->input('code');

        // Récupérer le token depuis la table
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        // Vérifier l'existence, la validité du code et l'expiration (15 min)
        $tokenAge = $record
            ? \Illuminate\Support\Carbon::parse($record->created_at)->diffInMinutes(now())
            : 0;

        if (
            ! $record
            || ! Hash::check($code, $record->token)
            || $tokenAge > 15
        ) {
            return back()
                ->withInput($request->only('email', 'code'))
                ->withErrors(['code' => 'Code invalide ou expiré. Veuillez recommencer.']);
        }

        // Mettre à jour le mot de passe
        $user = User::where('email', $email)->first();

        if (! $user) {
            return back()->withErrors(['email' => 'Aucun compte trouvé pour cet email.']);
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        // Supprimer le token utilisé
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return redirect()
            ->route('panel.login')
            ->with('status', 'Mot de passe réinitialisé avec succès. Vous pouvez vous connecter.');
    }
}
