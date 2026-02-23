<?php

namespace App\Services\Notification;

use App\Models\Notification;
use App\Models\NotificationReceiver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Create one notification and send to multiple users/admins (one row per receiver).
     */
    public function createAndSend(
        string $title,
        string $body = '',
        string $type = Notification::TYPE_GENERAL,
        ?array $data = null,
        array $userIds = [],
        array $adminIds = [],
        ?string $createdByType = null,
        ?int $createdById = null
    ): Notification {
        return DB::transaction(function () use ($title, $body, $type, $data, $userIds, $adminIds, $createdByType, $createdById) {
            $notification = Notification::create([
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'created_by_type' => $createdByType,
                'created_by_id' => $createdById,
            ]);

            $receivers = [];
            foreach (array_unique($userIds) as $userId) {
                $receivers[] = [
                    'notification_id' => $notification->id,
                    'recipient_type' => NotificationReceiver::RECIPIENT_USER,
                    'recipient_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            foreach (array_unique($adminIds) as $adminId) {
                $receivers[] = [
                    'notification_id' => $notification->id,
                    'recipient_type' => NotificationReceiver::RECIPIENT_ADMIN,
                    'recipient_id' => $adminId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (!empty($receivers)) {
                NotificationReceiver::insert($receivers);
            }

            return $notification->fresh('receivers');
        });
    }

    /**
     * List notifications for a user (via receivers).
     */
    public function listForUser(int $userId, ?string $readFilter = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = NotificationReceiver::with('notification')
            ->where('recipient_type', NotificationReceiver::RECIPIENT_USER)
            ->where('recipient_id', $userId)
            ->orderByDesc('created_at');

        if ($readFilter === 'read') {
            $query->whereNotNull('read_at');
        } elseif ($readFilter === 'unread') {
            $query->whereNull('read_at');
        }

        return $query->paginate($perPage);
    }

    /**
     * List notifications for an admin (via receivers).
     */
    public function listForAdmin(int $adminId, ?string $readFilter = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = NotificationReceiver::with('notification')
            ->where('recipient_type', NotificationReceiver::RECIPIENT_ADMIN)
            ->where('recipient_id', $adminId)
            ->orderByDesc('created_at');

        if ($readFilter === 'read') {
            $query->whereNotNull('read_at');
        } elseif ($readFilter === 'unread') {
            $query->whereNull('read_at');
        }

        return $query->paginate($perPage);
    }

    /**
     * Mark one receiver as read (user or admin must own this receiver).
     */
    public function markAsRead(int $receiverId, string $recipientType, int $recipientId): bool
    {
        $receiver = NotificationReceiver::where('id', $receiverId)
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->first();

        if (!$receiver || $receiver->read_at) {
            return false;
        }
        $receiver->update(['read_at' => now()]);
        return true;
    }

    /**
     * Mark all notifications as read for a user or admin.
     */
    public function markAllAsRead(string $recipientType, int $recipientId): int
    {
        return NotificationReceiver::where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Unread count for user or admin.
     */
    public function unreadCount(string $recipientType, int $recipientId): int
    {
        return NotificationReceiver::where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Fetch new notifications after last_id (for UI badge / fetch-next). Returns receivers with id > last_id.
     */
    public function getNewForUser(int $userId, ?int $lastId = null, int $limit = 50): \Illuminate\Support\Collection
    {
        $query = NotificationReceiver::with('notification')
            ->where('recipient_type', NotificationReceiver::RECIPIENT_USER)
            ->where('recipient_id', $userId)
            ->orderByDesc('id')
            ->limit($limit);

        if ($lastId !== null && $lastId > 0) {
            $query->where('id', '>', $lastId);
        }

        return $query->get();
    }

    /**
     * Fetch new notifications after last_id for admin.
     */
    public function getNewForAdmin(int $adminId, ?int $lastId = null, int $limit = 50): \Illuminate\Support\Collection
    {
        $query = NotificationReceiver::with('notification')
            ->where('recipient_type', NotificationReceiver::RECIPIENT_ADMIN)
            ->where('recipient_id', $adminId)
            ->orderByDesc('id')
            ->limit($limit);

        if ($lastId !== null && $lastId > 0) {
            $query->where('id', '>', $lastId);
        }

        return $query->get();
    }
}
