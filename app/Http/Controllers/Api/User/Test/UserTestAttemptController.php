<?php

namespace App\Http\Controllers\Api\User\Test;

use App\Http\Controllers\Controller;
use App\Services\User\Test\UserTestAttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserTestAttemptController extends Controller
{
    public function __construct(
        protected UserTestAttemptService $attemptService
    ) {}

    /**
     * Start test: generate questions and save. Returns attempt_id + questions.
     * POST /api/user/packages/{userPackageId}/tests/{testId}/start
     */
    public function start(Request $request, int $userPackageId, int $testId): JsonResponse
    {
        try {
            $data = $this->attemptService->startTest(
                $request->user(),
                $userPackageId,
                $testId
            );
            return response()->json($data, 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Package or test not found.'], 404);
        }
    }

    /**
     * Submit single answer. Body: question_id, question_option_id (MCQ), or answer_value (scale).
     * POST /api/user/attempts/{attemptId}/answer
     */
    public function submitAnswer(Request $request, int $attemptId): JsonResponse
    {
        $request->validate([
            'question_id' => 'required|integer',
            'question_option_id' => 'nullable|integer',
            'answer_value' => 'nullable|string|max:500',
        ]);

        if (!$request->question_option_id && !$request->has('answer_value')) {
            return response()->json([
                'message' => 'Provide question_option_id (MCQ) or answer_value.',
            ], 422);
        }

        try {
            $data = $this->attemptService->submitAnswer(
                $request->user(),
                $attemptId,
                (int) $request->question_id,
                $request->question_option_id ? (int) $request->question_option_id : null,
                $request->answer_value
            );
            return response()->json($data, 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Attempt not found.'], 404);
        }
    }

    /**
     * Generate score and get result (section-wise + total).
     * POST /api/user/attempts/{attemptId}/submit-score
     */
    public function submitScore(Request $request, int $attemptId): JsonResponse
    {
        try {
            $data = $this->attemptService->generateScore($request->user(), $attemptId);
            return response()->json($data, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Attempt not found.'], 404);
        }
    }

    /**
     * List my attempts (in_progress first, then completed).
     * GET /api/user/attempts
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->attemptService->listAttempts($request->user());
        return response()->json($data, 200);
    }

    /**
     * Get attempt details (resume or view result).
     * GET /api/user/attempts/{attemptId}
     */
    public function show(Request $request, int $attemptId): JsonResponse
    {
        try {
            $data = $this->attemptService->getAttempt($request->user(), $attemptId);
            return response()->json($data, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Attempt not found.'], 404);
        }
    }
}
