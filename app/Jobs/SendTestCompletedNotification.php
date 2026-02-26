<?php

namespace App\Jobs;

use App\Models\Admin;
use App\Models\Notification;
use App\Models\UserTestAttempt;
use App\Services\Notification\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTestCompletedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $attemptId
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $attempt = UserTestAttempt::with(['user', 'test'])->find($this->attemptId);
        if (!$attempt || $attempt->status !== UserTestAttempt::STATUS_COMPLETED) {
            return;
        }

        $testTitle = $attempt->test?->title ?? 'Test';
        $userName = $attempt->user
            ? trim(($attempt->user->first_name ?? '') . ' ' . ($attempt->user->last_name ?? ''))
            : 'A user';
        if ($userName === '' && $attempt->user) {
            $userName = $attempt->user->email ?? 'A user';
        }

        $data = [
            'attempt_id' => $attempt->id,
            'test_id' => $attempt->test_id,
            'test_title' => $testTitle,
            'user_id' => $attempt->user_id,
        ];

        // Notify the user: test completed
        $notificationService->createAndSend(
            'Test completed',
            'You have completed "' . $testTitle . '". View your result in Attempts.',
            Notification::TYPE_TEST_RESULT,
            $data,
            [$attempt->user_id],
            [],
            'user',
            $attempt->user_id
        );

        // Notify all admins
        $adminIds = Admin::where('status', 'active')->pluck('id')->toArray();
        if (!empty($adminIds)) {
            $notificationService->createAndSend(
                'User completed test',
                $userName . ' has completed the test "' . $testTitle . '". Attempt #' . $attempt->id . '.',
                Notification::TYPE_TEST_RESULT,
                $data,
                [],
                $adminIds,
                'user',
                $attempt->user_id
            );
        }
    }
}
