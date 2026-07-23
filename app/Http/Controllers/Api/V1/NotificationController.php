<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => $this->notificationPayload($notification))
            ->values();

        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
            'data' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        $notificationModel = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $notificationModel->markAsRead();

        return response()->json([
            'data' => $this->notificationPayload($notificationModel->refresh()),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'data' => [
                'status' => 'markedAsRead',
            ],
        ]);
    }

    /**
     * @return array{
     *     id: string,
     *     userId: int|string,
     *     actorId: int|string|null,
     *     actorName: string|null,
     *     actorAvatarUrl: string|null,
     *     type: string,
     *     taskId: int|string|null,
     *     isRead: bool,
     *     createdAt: string|null
     * }
     */
    private function notificationPayload(DatabaseNotification $notification): array
    {
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'userId' => $notification->notifiable_id,
            'actorId' => $data['actorId'] ?? null,
            'actorName' => $data['actorName'] ?? null,
            'actorAvatarUrl' => $data['actorAvatarUrl'] ?? null,
            'type' => $data['type'] ?? class_basename($notification->type),
            'taskId' => $data['taskId'] ?? null,
            'isRead' => $notification->read_at !== null,
            'createdAt' => $notification->created_at?->toIso8601String(),
        ];
    }
}
