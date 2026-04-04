<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Services\FcmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminNotificationsController extends Controller
{
    public function __construct(private readonly FcmService $fcm) {}

    // =========================================================================
    // GET /panel/admin/notifications/send
    // =========================================================================

    /**
     * Show the notification compose form with live token stats.
     */
    public function create(): View
    {
        $totalTokens      = \App\Models\FcmToken::where('is_active', true)->count();
        $subscriberTokens = \App\Models\FcmToken::where('is_active', true)
            ->whereHas('user', fn ($q) => $q->where('role', 'subscriber'))
            ->count();
        $advertiserTokens = \App\Models\FcmToken::where('is_active', true)
            ->whereHas('user', fn ($q) => $q->where('role', 'advertiser'))
            ->count();

        return view('panel.admin.send-notification', compact(
            'totalTokens',
            'subscriberTokens',
            'advertiserTokens',
        ));
    }

    // =========================================================================
    // POST /panel/admin/notifications/send
    // =========================================================================

    /**
     * Dispatch the push notification via FCM.
     *
     * Targets:
     *   - all          → every active user
     *   - subscribers  → active subscribers only
     *   - advertisers  → active advertisers only
     *   - user         → a specific user by ID
     */
    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'   => ['required', 'string', 'max:100'],
            'body'    => ['required', 'string', 'max:500'],
            'target'  => ['required', 'string', 'in:all,subscribers,advertisers,user'],
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'required_if:target,user'],
            'data'    => ['nullable', 'array'],
        ]);

        $title  = $validated['title'];
        $body   = $validated['body'];
        $extra  = $validated['data'] ?? [];

        $sent = match ($validated['target']) {
            'subscribers' => $this->fcm->sendToAllSubscribers($title, $body, $extra),
            'advertisers' => $this->fcm->sendToAllAdvertisers($title, $body, $extra),
            'user'        => $this->fcm->sendToUser(
                \App\Models\User::findOrFail($validated['user_id']),
                $title,
                $body,
                $extra,
            ),
            default       => $this->fcm->sendToAll($title, $body, $extra), // 'all'
        };

        return redirect()
            ->route('panel.admin.notifications.send')
            ->with('success', "Notification envoyée à {$sent} appareil(s) avec succès.");
    }
}
