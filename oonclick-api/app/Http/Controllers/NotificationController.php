<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class NotificationController extends Controller
{
    /**
     * Retourne les notifications paginées de l'utilisateur authentifié.
     *
     * Paramètre optionnel :
     *   - ?unread=true  → filtre uniquement les notifications non lues
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $request->boolean('unread')
            ? $user->unreadNotifications()
            : $user->notifications();

        $notifications = $query->paginate(20);

        return response()->json([
            'data'         => $notifications->items(),
            'unread_count' => $user->unreadNotifications()->count(),
            'meta'         => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'per_page'     => $notifications->perPage(),
                'total'        => $notifications->total(),
            ],
        ], 200);
    }

    /**
     * Marque une notification spécifique comme lue.
     *
     * @param Request $request
     * @param string  $id  UUID de la notification
     * @return JsonResponse
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->find($id);

        if (! $notification) {
            return response()->json(['message' => 'Notification introuvable.'], 404);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marquée comme lue.'], 200);
    }

    /**
     * Marque toutes les notifications non lues comme lues.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Toutes les notifications ont été marquées comme lues.'], 200);
    }

    /**
     * Supprime une notification.
     *
     * @param Request $request
     * @param string  $id  UUID de la notification
     * @return JsonResponse
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->find($id);

        if (! $notification) {
            return response()->json(['message' => 'Notification introuvable.'], 404);
        }

        $notification->delete();

        return response()->json(['message' => 'Notification supprimée.'], 200);
    }
}
