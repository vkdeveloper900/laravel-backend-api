<?php

namespace App\Jobs;

use App\Models\Admin;
use App\Models\Notification;
use App\Models\Order;
use App\Services\Notification\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPackagePurchasedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $orderId
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $order = Order::with(['user', 'package'])->find($this->orderId);
        if (!$order || $order->status !== 'paid') {
            return;
        }

        $user = $order->user;
        $packageName = $order->package?->name ?? 'Package';
        $userName = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'A user';
        if ($userName === '') {
            $userName = $user->email ?? 'A user';
        }

        $data = [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'package_id' => $order->package_id,
            'package_name' => $packageName,
        ];

        // Notify the user who bought: "Your package is activated"
        $notificationService->createAndSend(
            'Package activated',
            'You have successfully purchased "' . $packageName . '". You can start taking tests now.',
            Notification::TYPE_ORDER,
            $data,
            [$order->user_id],
            [],
            'user',
            $order->user_id
        );

        // Notify all admins: "User X purchased package Y"
        $adminIds = Admin::where('status', 'active')->pluck('id')->toArray();
        if (!empty($adminIds)) {
            $notificationService->createAndSend(
                'New package purchase',
                $userName . ' has purchased "' . $packageName . '". Order #' . $order->id . '.',
                Notification::TYPE_ORDER,
                $data,
                [],
                $adminIds,
                'user',
                $order->user_id
            );
        }
    }
}
