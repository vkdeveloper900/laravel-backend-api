<?php

namespace App\Http\Controllers\Api\User\Package;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\UserPackage;
use Illuminate\Http\Request;

class UserPackageController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $packages = Package::with(['tests'])
            ->where('status', 1)
            ->get()
            ->map(function ($package) use ($user) {

                $activePurchase = UserPackage::where('user_id', $user->id)
                    ->where('package_id', $package->id)
                    ->where('status', 'active')
                    ->where('expiry_date', '>', now())
                    ->first();

                return [
                    'id' => $package->id,
                    'title' => $package->name,
                    'description' => $package->description,
                    'price' => $package->price,
                    'validity_days' => $package->validity_days,
                    'total_tests' => $package->tests->count(),
                    'is_purchased' => $activePurchase ? true : false,
                    'expiry_date' => $activePurchase?->expiry_date,
                    'tests' => $package->tests->map(function ($test) {
                        return [
                            'id' => $test->id,
                            'title' => $test->title,
                            'difficulty' => $test->difficulty,
                            'total_time' => $test->total_time,
                        ];
                    }),
                ];
            });

        return response()->json([
            'data' => $packages
        ]);
    }

    /**
     * My purchased packages (for starting tests – has user_package_id).
     * GET /api/user/packages/my
     */
    public function myPackages(Request $request)
    {
        $user = $request->user();

        $userPackages = UserPackage::with('package.tests')
            ->where('user_id', $user->id)
            ->orderByDesc('activated_at')
            ->get()
            ->map(function ($up) {
                $isActive = $up->isActive();
                return [
                    'user_package_id' => $up->id,
                    'package_id' => $up->package_id,
                    'package_name' => $up->package->name ?? '',
                    'activated_at' => $up->activated_at ? (is_string($up->activated_at) ? $up->activated_at : $up->activated_at->format('c')) : null,
                    'expiry_date' => $up->expiry_date ? (is_string($up->expiry_date) ? $up->expiry_date : $up->expiry_date->format('Y-m-d')) : null,
                    'status' => $up->status,
                    'is_active' => $isActive,
                    'attempts_used' => $up->attempts_used ?? 0,
                    'max_attempts' => $up->max_attempts,
                    'tests_count' => $up->package->tests->count() ?? 0,
                    'tests' => $up->package->tests->map(fn ($t) => [
                        'id' => $t->id,
                        'title' => $t->title,
                        'total_time' => $t->total_time,
                    ])->values(),
                ];
            });

        return response()->json([
            'message' => 'My packages.',
            'data' => $userPackages,
        ]);
    }

    public function show($id, Request $request)
    {
        $package = Package::with('tests')
            ->where('status', 1)
            ->findOrFail($id);

        return response()->json([
            'data' => $package
        ]);
    }
}
