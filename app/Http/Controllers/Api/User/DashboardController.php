<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\NotificationReceiver;
use App\Models\Order;
use App\Models\UserPackage;
use App\Models\UserTestAttempt;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * User dashboard: stats and optional recent activity.
     * GET /api/user/dashboard
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user->id;

        $activePackagesCount = UserPackage::where('user_id', $userId)
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->count();

        $totalOrders = Order::where('user_id', $userId)->count();

        $completedTestsCount = UserTestAttempt::where('user_id', $userId)
            ->where('status', UserTestAttempt::STATUS_COMPLETED)
            ->count();

        $unreadNotificationsCount = $this->notificationService->unreadCount(
            NotificationReceiver::RECIPIENT_USER,
            $userId
        );

        $recentAttempts = UserTestAttempt::with('test')
            ->where('user_id', $userId)
            ->orderByDesc('started_at')
            ->limit(5)
            ->get()
            ->map(function (UserTestAttempt $a) {
                return [
                    'attempt_id' => $a->id,
                    'test_id' => $a->test_id,
                    'test_title' => $a->test->title ?? '',
                    'status' => $a->status,
                    'started_at' => $a->started_at->toIso8601String(),
                    'completed_at' => $a->completed_at?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Dashboard.',
            'data' => [
                'stats' => [
                    'active_packages' => $activePackagesCount,
                    'total_orders' => $totalOrders,
                    'tests_completed' => $completedTestsCount,
                    'unread_notifications' => $unreadNotificationsCount,
                ],
                'recent_attempts' => $recentAttempts,
            ],
        ]);
    }
}
