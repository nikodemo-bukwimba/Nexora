<?php

namespace Modules\Notifications\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notifications\Services\NotificationService;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notifications) {}

    /** GET /api/v1/notifications */
    public function index(Request $request): JsonResponse
    {
        $notifications = $this->notifications->list(
            $request->user()->actor_id,
            $request->only(['type', 'status']),
            (int) $request->get('per_page', 25)
        );

        $unread = $this->notifications->unreadCount($request->user()->actor_id);

        return response()->json([
            'unread_count'   => $unread,
            'notifications'  => $notifications,
        ]);
    }

    /** POST /api/v1/notifications/{id}/read */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $this->notifications->markRead($id, $request->user()->actor_id);
        return response()->json(['message' => 'Marked as read.', 'notification' => $notification]);
    }

    /** POST /api/v1/notifications/read-all */
    public function markAllRead(Request $request): JsonResponse
    {
        $this->notifications->markAllRead($request->user()->actor_id);
        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /** POST /api/v1/notifications/devices */
    public function registerDevice(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'platform' => ['required', 'string', 'in:android,ios,web'],
            'driver'   => ['nullable', 'string', 'in:fcm,apns,web-push'],
        ]);

        $token = $this->notifications->registerDevice(
            $request->user()->actor_id,
            $request->token,
            $request->platform,
            $request->driver ?? 'fcm',
            $request->device_name ?? null
        );

        return response()->json(['message' => 'Device registered.', 'token' => $token], 201);
    }

    /** DELETE /api/v1/notifications/devices */
    public function deregisterDevice(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string']]);
        $this->notifications->deregisterDevice($request->token);
        return response()->json(['message' => 'Device deregistered.']);
    }

    /** GET /api/v1/notifications/preferences */
    public function preferences(Request $request): JsonResponse
    {
        return response()->json($this->notifications->getPreferences($request->user()->actor_id));
    }

    /** PATCH /api/v1/notifications/preferences/{type} */
    public function updatePreference(Request $request, string $type): JsonResponse
    {
        $pref = $this->notifications->updatePreference(
            $request->user()->actor_id,
            $type,
            $request->only(['push', 'email', 'sms'])
        );
        return response()->json(['message' => 'Preference updated.', 'preference' => $pref]);
    }
}
