<?php

namespace App\Services;

use App\Models\FcmToken;
use App\Models\User;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FcmService
{
    public function __construct(
        private readonly \Kreait\Firebase\Contract\Messaging $messaging
    ) {}

    // =========================================================================
    // Per-user dispatch
    // =========================================================================

    /**
     * Send a push notification to all active devices of a single user.
     *
     * Returns the number of messages successfully delivered.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): int
    {
        $tokens = FcmToken::where('user_id', $user->id)
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return 0;
        }

        $message = CloudMessage::new()
            ->withNotification(Notification::create($title, $body))
            ->withData($this->stringifyData($data));

        $report = $this->messaging->sendMulticast($message, $tokens);

        $this->deactivateInvalidTokens($report->invalidTokens());

        return $report->successes()->count();
    }

    // =========================================================================
    // Multi-user dispatch
    // =========================================================================

    /**
     * Send a push notification to all active devices of the given user IDs.
     *
     * Sends in batches of 500 to stay within the FCM multicast limit.
     * Returns the total number of messages successfully delivered.
     */
    public function sendToUsers(array $userIds, string $title, string $body, array $data = []): int
    {
        $tokens = FcmToken::whereIn('user_id', $userIds)
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return 0;
        }

        $message = CloudMessage::new()
            ->withNotification(Notification::create($title, $body))
            ->withData($this->stringifyData($data));

        $sent = 0;

        foreach (array_chunk($tokens, 500) as $batch) {
            $report = $this->messaging->sendMulticast($message, $batch);
            $sent  += $report->successes()->count();

            $this->deactivateInvalidTokens($report->invalidTokens());
        }

        return $sent;
    }

    // =========================================================================
    // Topic-based broadcast
    // =========================================================================

    /**
     * Send a push notification to a Firebase topic.
     *
     * Common topics: 'all_subscribers', 'all_advertisers', 'all_users'.
     * Users must be subscribed to the topic on the client side.
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): void
    {
        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification(Notification::create($title, $body))
            ->withData($this->stringifyData($data));

        $this->messaging->send($message);
    }

    // =========================================================================
    // Role-based helpers
    // =========================================================================

    /**
     * Broadcast to all active subscribers.
     */
    public function sendToAllSubscribers(string $title, string $body, array $data = []): int
    {
        $ids = \App\Models\User::subscribers()->active()->pluck('id')->toArray();

        return $this->sendToUsers($ids, $title, $body, $data);
    }

    /**
     * Broadcast to all active advertisers.
     */
    public function sendToAllAdvertisers(string $title, string $body, array $data = []): int
    {
        $ids = \App\Models\User::advertisers()->active()->pluck('id')->toArray();

        return $this->sendToUsers($ids, $title, $body, $data);
    }

    /**
     * Broadcast to every registered user.
     */
    public function sendToAll(string $title, string $body, array $data = []): int
    {
        $ids = \App\Models\User::active()->pluck('id')->toArray();

        return $this->sendToUsers($ids, $title, $body, $data);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * FCM data payloads require all values to be strings.
     */
    private function stringifyData(array $data): array
    {
        return array_map('strval', $data);
    }

    /**
     * Mark FCM tokens that Firebase rejected as inactive so we stop sending
     * to stale / unregistered tokens.
     *
     * @param iterable<string> $tokens
     */
    private function deactivateInvalidTokens(iterable $tokens): void
    {
        foreach ($tokens as $token) {
            FcmToken::where('token', $token)->update(['is_active' => false]);
        }
    }
}
