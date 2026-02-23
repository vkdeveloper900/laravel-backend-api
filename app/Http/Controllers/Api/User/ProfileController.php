<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Get my profile.
     * GET /api/user/profile
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'message' => 'Profile.',
            'data' => $this->formatUser($user),
        ]);
    }

    /**
     * Update my profile.
     * PUT /api/user/profile  or  PATCH /api/user/profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->only(['first_name', 'last_name', 'dob', 'phone']);

        if ($request->filled('email')) {
            $data['email'] = $request->email;
        }

        if ($request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Current password is incorrect.'], 422);
            }
            $data['password'] = $request->password;
        }

        if (!empty($data)) {
            $user->update($data);
            if (isset($data['first_name']) || isset($data['last_name'])) {
                $user->name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                $user->save();
            }
        }

        return response()->json([
            'message' => 'Profile updated.',
            'data' => $this->formatUser($user->fresh()),
        ]);
    }

    protected function formatUser($user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => $user->name,
            'email' => $user->email,
            'dob' => $user->dob?->format('Y-m-d'),
            'phone' => $user->phone,
            'status' => $user->status,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'created_at' => $user->created_at->toIso8601String(),
            'updated_at' => $user->updated_at->toIso8601String(),
        ];
    }
}
