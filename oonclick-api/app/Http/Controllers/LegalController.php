<?php

namespace App\Http\Controllers;

use App\Models\UserConsent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LegalController extends Controller
{
    public function cgu()
    {
        return view('legal.cgu');
    }

    public function privacy()
    {
        return view('legal.privacy');
    }

    public function consents()
    {
        $user = Auth::user();

        $consents = UserConsent::where('user_id', $user->id)
            ->get()
            ->keyBy('consent_type');

        return view('legal.consents', compact('user', 'consents'));
    }

    public function updateConsents(Request $request)
    {
        $user = Auth::user();
        $ip   = $request->ip();
        $ua   = $request->userAgent();

        // Only optional consents C5 and C6 can be toggled
        $optional = ['C5', 'C6'];

        foreach ($optional as $type) {
            $granted = $request->boolean('consent_' . strtolower($type));
            UserConsent::record($user->id, $type, $granted, $ip, $ua);
        }

        return redirect()->route('legal.consents')
            ->with('success', 'Vos preferences de consentement ont ete mises a jour.');
    }
}
