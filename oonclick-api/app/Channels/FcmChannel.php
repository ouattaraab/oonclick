<?php

namespace App\Channels;

use App\Services\FcmService;
use Illuminate\Notifications\Notification;

/**
 * Custom Laravel notification channel for Firebase Cloud Messaging.
 *
 * To use this channel in a notification class:
 *   1. Add FcmChannel::class to the via() return array.
 *   2. Implement a toFcm($notifiable): array method that returns:
 *      [
 *          'title' => string,
 *          'body'  => string,
 *          'data'  => array (optional, key-value pairs of additional payload),
 *      ]
 */
class FcmChannel
{
    public function __construct(private readonly FcmService $fcm) {}

    /**
     * Deliver the notification via FCM.
     *
     * Silently skips if the notification does not implement toFcm().
     *
     * @param  mixed        $notifiable  The model being notified (e.g. User)
     * @param  Notification $notification
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $data = $notification->toFcm($notifiable);

        $this->fcm->sendToUser(
            $notifiable,
            $data['title'],
            $data['body'],
            $data['data'] ?? [],
        );
    }
}
