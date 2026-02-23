<?php

use App\Http\Controllers\Api\Admin\Auth\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\Coupon\CouponController;
use App\Http\Controllers\Api\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Api\Admin\Meta\MetaController;
use App\Http\Controllers\Api\Admin\Meta\QuestionImportExportController;
use App\Http\Controllers\Api\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Api\Admin\Order\OrderController;
use App\Http\Controllers\Api\Admin\Order\PaymentController;
use App\Http\Controllers\Api\Admin\Package\PackageController;
use App\Http\Controllers\Api\Admin\RolePermission\RolePermissionController;
use App\Http\Controllers\Api\Admin\Test\Question\QuestionController;
use App\Http\Controllers\Api\Admin\Test\Section\SectionController;
use App\Http\Controllers\Api\Admin\Test\TestController;
use App\Http\Controllers\Api\Admin\Test\TestSection\TestSectionController;
use App\Http\Controllers\Api\TestNotificationController;
use App\Http\Controllers\Api\User\Test\TestQuestionController;
use App\Http\Middleware\Permissions\CheckPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API HEALTH CHECK
|--------------------------------------------------------------------------
| Basic API status check endpoint
*/
Route::get('init', function () {
    return response()->json(['message' => 'Api Running.']);
});

Route::get('deploy', function () {
    return response()->json(['message' => 'Api Running.']);
});

Route::get('/test-whatsapp', [TestNotificationController::class, 'sendWhatsapp']);

/*
|--------------------------------------------------------------------------
| QUESTION SAMPLE DOWNLOAD (PUBLIC)
|--------------------------------------------------------------------------
*/
Route::get('questions/sample', [QuestionImportExportController::class, 'downloadSample']);

/*
|--------------------------------------------------------------------------
| ADMIN AUTHENTICATION ROUTES (PUBLIC)
|--------------------------------------------------------------------------
| - Register
| - Login
| - Logout
| - Two-Factor Authentication
*/
Route::prefix('admin/auth')->group(function () {

    Route::post('register', [AdminAuthController::class, 'register']);
    Route::post('login', [AdminAuthController::class, 'login']);

    Route::post('logout', [AdminAuthController::class, 'logout'])
        ->middleware(['auth:sanctum', 'ensure.admin']);

    Route::post('two-factor/start', [AdminAuthController::class, 'startTwoFactor']);
    Route::post('two-factor/verify', [AdminAuthController::class, 'verifyTwoFactor']);
});

/*
|--------------------------------------------------------------------------
| ADMIN PROTECTED ROUTES
|--------------------------------------------------------------------------
| Middleware:
| - auth:sanctum
| - ensure.admin
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth:sanctum', 'ensure.admin'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | AUTH CHECK
    |--------------------------------------------------------------------------
    */
    Route::get('init', function () {
        return response()->json(['message' => 'You Authenticate User.']);
    })->middleware(CheckPermission::class);

    /*
    |--------------------------------------------------------------------------
    | META DATA MODULE
    |--------------------------------------------------------------------------
    | Dropdown constants and static configuration values
    */
    Route::prefix('meta')->group(function () {
        Route::get('difficulties', [MetaController::class, 'difficulties']);
        Route::get('question-types', [MetaController::class, 'questionTypes']);
        Route::get('test-statuses', [MetaController::class, 'testStatuses']);
        Route::get('yes-no', [MetaController::class, 'yesNo']);
    });

    /*
    |--------------------------------------------------------------------------
    | TEST MANAGEMENT MODULE
    |--------------------------------------------------------------------------
    | - Section Master
    | - Question Bank
    | - Test Master
    | - Test Section Rules
    | - Question Import
    |--------------------------------------------------------------------------
    */
    Route::prefix('test')->group(function () {

        // SECTION MASTER
        Route::prefix('sections')->group(function () {
            Route::get('/', [SectionController::class, 'index']);
            Route::post('/', [SectionController::class, 'store']);
            Route::get('{id}', [SectionController::class, 'show']);
            Route::put('{id}', [SectionController::class, 'update']);
            Route::delete('{id}', [SectionController::class, 'destroy']);
        });

        // QUESTION BANK
        Route::prefix('questions')->group(function () {
            Route::get('/', [QuestionController::class, 'index']);
            Route::post('/', [QuestionController::class, 'store']);
            Route::get('{id}', [QuestionController::class, 'show']);
            Route::put('{id}', [QuestionController::class, 'update']);
            Route::delete('{id}', [QuestionController::class, 'destroy']);
        });

        // TEST MASTER
        Route::prefix('tests')->group(function () {
            Route::get('/', [TestController::class, 'index']);
            Route::post('/', [TestController::class, 'store']);
            Route::get('{id}', [TestController::class, 'show']);
            Route::put('{id}', [TestController::class, 'update']);
            Route::delete('{id}', [TestController::class, 'destroy']);
            Route::post('{id}/publish', [TestController::class, 'publish']);
        });

        // TEST SECTION CONFIGURATION
        Route::prefix('tests/{testId}')->group(function () {
            Route::get('sections', [TestSectionController::class, 'index']);
            Route::post('sections', [TestSectionController::class, 'store']);
            Route::delete('sections/{id}', [TestSectionController::class, 'destroy']);

            Route::get('debug-generate', [TestController::class, 'debugGenerateQuestions']);
            Route::get('start', [TestQuestionController::class, 'startTest']);
        });

        // IMPORT QUESTIONS
        Route::prefix('import')->group(function () {
            Route::get('questions/sample', [QuestionImportExportController::class, 'downloadSample']);
            Route::post('questions', [QuestionImportExportController::class, 'import']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | PACKAGE MODULE
    |--------------------------------------------------------------------------
    */
    Route::prefix('packages')->group(function () {
        Route::get('/', [PackageController::class, 'index']);
        Route::post('/', [PackageController::class, 'store']);
        Route::get('{id}', [PackageController::class, 'show']);
        Route::put('{id}', [PackageController::class, 'update']);
        Route::delete('{id}', [PackageController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | COUPON MODULE
    |--------------------------------------------------------------------------
    */
    Route::prefix('coupons')->group(function () {
        Route::get('/', [CouponController::class, 'index']);
        Route::post('/', [CouponController::class, 'store']);
        Route::get('{id}', [CouponController::class, 'show']);
        Route::put('{id}', [CouponController::class, 'update']);
        Route::delete('{id}', [CouponController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | ORDER & PAYMENT MODULE
    |--------------------------------------------------------------------------
    | Order lifecycle + nested payments
    */
    Route::prefix('orders')->group(function () {

        Route::get('/', [OrderController::class, 'index']);
        Route::get('stats', [OrderController::class, 'stats']);
        Route::get('{id}', [OrderController::class, 'show']);
        Route::put('{id}/status', [OrderController::class, 'updateStatus']);

        // ORDER PAYMENTS
        Route::get('{orderId}/payments', [PaymentController::class, 'orderPayments']);
    });

    /*
    |--------------------------------------------------------------------------
    | PAYMENT MODULE (GLOBAL VIEW)
    |--------------------------------------------------------------------------
    */
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::get('{id}', [PaymentController::class, 'show']);
    });

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD MODULE
    |--------------------------------------------------------------------------
    | Admin analytics & statistics
    */
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
    });

    /*
    |--------------------------------------------------------------------------
    | ROLE & PERMISSION MODULE
    |--------------------------------------------------------------------------
    */
    Route::prefix('access')->group(function () {

        Route::get('roles', [RolePermissionController::class, 'roles']);
        Route::post('roles', [RolePermissionController::class, 'createRole']);
        Route::get('roles/{id}', [RolePermissionController::class, 'roleDetails']);
        Route::put('roles/{id}', [RolePermissionController::class, 'updateRole']);
        Route::delete('roles/{id}', [RolePermissionController::class, 'deleteRole']);

        Route::get('permissions', [RolePermissionController::class, 'permissions']);
        Route::post('roles/{id}/permissions', [RolePermissionController::class, 'assignPermissions']);
    });

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATIONS (list, mark read, send to users/admins)
    |--------------------------------------------------------------------------
    */
    Route::prefix('notifications')->group(function () {
        Route::get('/', [AdminNotificationController::class, 'index']);
        Route::get('new', [AdminNotificationController::class, 'getNew']);
        Route::get('unread-count', [AdminNotificationController::class, 'unreadCount']);
        Route::post('mark-all-read', [AdminNotificationController::class, 'markAllRead']);
        Route::post('send', [AdminNotificationController::class, 'send']);
        Route::patch('{receiverId}/read', [AdminNotificationController::class, 'markRead']);
    });

    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATED ADMIN PROFILE
    |--------------------------------------------------------------------------
    */
    Route::get('me', function (Request $request) {
        return $request->user();
    })->middleware(CheckPermission::class . ':package.create,package.update');
});
