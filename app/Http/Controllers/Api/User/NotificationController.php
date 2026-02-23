<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\NotificationReceiver;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * List my notifications. Query: ?read=all|read|unread, ?per_page=15
     * GET /api/user/notifications
     */
    public function index(Request $request): JsonResponse
    {
        $read = $request->query('read', 'all');
        $perPage = min((int) $request->query('per_page', 15), 50);

        $paginator = $this->notificationService->listForUser(
            $request->user()->id,
            $read === 'all' ? null : $read,
            $perPage
        );

        $items = $paginator->getCollection()->map(function (NotificationReceiver $r) {
            return $this->formatReceiver($r);
        });

        return response()->json([
            'message' => 'My notifications.',
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'unread_count' => $this->notificationService->unreadCount(NotificationReceiver::RECIPIENT_USER, $request->user()->id),
        ]);
    }

    /**
     * Mark single notification as read.
     * PATCH /api/user/notifications/{receiverId}/read
     */
    public function markRead(Request $request, int $receiverId): JsonResponse
    {
        $ok = $this->notificationService->markAsRead(
            $receiverId,
            NotificationReceiver::RECIPIENT_USER,
            $request->user()->id
        );
        if (!$ok) {
            return response()->json(['message' => 'Notification not found or already read.'], 404);
        }
        return response()->json(['message' => 'Marked as read.']);
    }

    /**
     * Mark all my notifications as read.
     * POST /api/user/notifications/mark-all-read
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead(
            NotificationReceiver::RECIPIENT_USER,
            $request->user()->id
        );
        return response()->json([
            'message' => 'All notifications marked as read.',
            'count' => $count,
        ]);
    }

    /**
     * Unread count only.
     * GET /api/user/notifications/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->unreadCount(
            NotificationReceiver::RECIPIENT_USER,
            $request->user()->id
        );
        return response()->json(['unread_count' => $count]);
    }

    /**
     * Get new notifications after last_id (UI sends last notification's receiver_id; API returns next/new ones).
     * GET /api/user/notifications/new?last_id=123&limit=50
     */
    public function getNew(Request $request): JsonResponse
    {
        $lastId = $request->query('last_id') ? (int) $request->query('last_id') : null;
        $limit = min((int) $request->query('limit', 50), 100);

        $receivers = $this->notificationService->getNewForUser($request->user()->id, $lastId, $limit);
        $items = $receivers->map(fn (NotificationReceiver $r) => $this->formatReceiver($r))->values();

        $latestId = $receivers->isEmpty() ? $lastId : $receivers->first()->id;

        return response()->json([
            'message' => 'New notifications.',
            'data' => $items,
            'latest_receiver_id' => $latestId,
            'count' => $items->count(),
        ]);
    }

    protected function formatReceiver(NotificationReceiver $r): array
    {
        $n = $r->notification;
        return [
            'receiver_id' => $r->id,
            'notification_id' => $n->id,
            'type' => $n->type,
            'title' => $n->title,
            'body' => $n->body,
            'data' => $n->data,
            'read_at' => $r->read_at?->toIso8601String(),
            'created_at' => $r->created_at->toIso8601String(),
        ];
    }
}
