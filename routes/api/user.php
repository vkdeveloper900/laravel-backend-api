<?php

use App\Http\Controllers\Api\User\Auth\AuthController as UserAuthController;
use App\Http\Controllers\Api\User\Auth\SocialAuthController;
use App\Http\Controllers\Api\User\Order\UserOrderController;
use App\Http\Controllers\Api\User\NotificationController;
use App\Http\Controllers\Api\User\Package\UserPackageController;
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\DashboardController;
use App\Http\Controllers\Api\User\Test\UserTestAttemptController;
use App\Http\Controllers\Api\User\Test\UserTestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| USER AUTH ROUTES (PUBLIC)
|--------------------------------------------------------------------------
| - Register
| - Login
| - Logout
| - OTP Login
| - Social Login
|--------------------------------------------------------------------------
*/
Route::prefix('user/auth')->group(function () {

    // Register new user
    Route::post('register', [UserAuthController::class, 'register']);

    // Login with email/password
    Route::post('login', [UserAuthController::class, 'login']);

    // Logout (Authenticated)
    Route::post('logout', [UserAuthController::class, 'logout'])
        ->middleware(['auth:sanctum', 'ensure.user']);

    /*
    |--------------------------------------------------------------------------
    | OTP AUTHENTICATION
    |--------------------------------------------------------------------------
    */
    Route::post('otp/request', [UserAuthController::class, 'requestOtp']);
    Route::post('otp/verify', [UserAuthController::class, 'verifyOtp']);

    /*
    |--------------------------------------------------------------------------
    | SOCIAL AUTHENTICATION
    |--------------------------------------------------------------------------
    */
    Route::post('social/{provider}', [SocialAuthController::class, 'handle']);
});


/*
|--------------------------------------------------------------------------
| USER PROTECTED ROUTES
|--------------------------------------------------------------------------
| Middleware:
| - auth:sanctum
| - ensure.user
|--------------------------------------------------------------------------
*/
Route::prefix('user')->middleware(['auth:sanctum', 'ensure.user'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | AUTH CHECK
    |--------------------------------------------------------------------------
    */
    Route::get('init', function () {
        return response()->json([
            'message' => 'You Authenticate User.'
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::patch('profile', [ProfileController::class, 'update']);

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    Route::get('dashboard', [DashboardController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | PACKAGE MODULE
    |--------------------------------------------------------------------------
    | - List available packages
    | - View package details
    | - Buy package
    |--------------------------------------------------------------------------
    */
    Route::prefix('packages')->group(function () {
        Route::get('/', [UserPackageController::class, 'index']);
        Route::get('my', [UserPackageController::class, 'myPackages']);
        Route::get('{id}', [UserPackageController::class, 'show']);
        Route::post('{id}/buy', [UserOrderController::class, 'buy']);
        Route::post('{userPackageId}/tests/{testId}/start', [UserTestAttemptController::class, 'start']);
    });

    /*
    |--------------------------------------------------------------------------
    | ORDER MODULE
    |--------------------------------------------------------------------------
    | - List user orders
    | - View order details
    |--------------------------------------------------------------------------
    */
    Route::prefix('orders')->group(function () {

        // My Orders
        Route::get('/', [UserOrderController::class, 'index']);
        // Single Order
        Route::get('{id}', [UserOrderController::class, 'show']);

        // Create Razorpay order
        Route::post('{orderId}/pay', [UserOrderController::class, 'pay']);

        // Verify payment
        Route::post('{orderId}/verify', [UserOrderController::class, 'verify']);
    });

    Route::prefix('tests')->group(function () {
        // List tests of active package
        Route::get('/', [UserTestController::class, 'index']);
    });

    /*
    |--------------------------------------------------------------------------
    | TEST ATTEMPT (Submit answer → Generate score)
    |--------------------------------------------------------------------------
    */
    Route::prefix('attempts')->group(function () {
        Route::get('/', [UserTestAttemptController::class, 'index']);
        Route::get('{attemptId}', [UserTestAttemptController::class, 'show']);
        Route::post('{attemptId}/answer', [UserTestAttemptController::class, 'submitAnswer']);
        Route::post('{attemptId}/submit-score', [UserTestAttemptController::class, 'submitScore']);
    });

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATIONS
    |--------------------------------------------------------------------------
    */
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('new', [NotificationController::class, 'getNew']);
        Route::get('unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('mark-all-read', [NotificationController::class, 'markAllRead']);
        Route::patch('{receiverId}/read', [NotificationController::class, 'markRead']);
    });
});
