<?php

namespace App\Http\Controllers\Api\User\Test;

use App\Http\Controllers\Controller;
use App\Models\UserPackage;
use Illuminate\Http\Request;

class UserTestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get active package
        $activePackage = UserPackage::with('package.tests')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->first();

        if (!$activePackage) {
            return response()->json([
                'message' => 'No active package found.',
                'package' => null,
                'tests' => [],
            ], 200);
        }

        $tests = $activePackage->package->tests
//            ->where('status', 'published') todo
            ->map(function ($test) {

                return [
                    'id' => $test->id,
                    'title' => $test->title,
                    'difficulty' => $test->difficulty,
                    'total_time' => $test->total_time,
                    'total_questions' => $test->total_questions,
                ];
            });

        return response()->json([
            'package' => [
                'id' => $activePackage->package->id,
                'title' => $activePackage->package->name ?? $activePackage->package->title ?? 'Package',
                'expiry_date' => $activePackage->expiry_date,
            ],
            'tests' => $tests
        ]);
    }
}
