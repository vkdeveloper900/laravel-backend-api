<?php

namespace App\Services\User\Test;

use App\Models\Question;
use App\Models\Test;
use App\Models\User;
use App\Models\UserPackage;
use App\Models\UserTestAttempt;
use App\Models\UserTestAttemptQuestion;
use App\Models\UserTestAnswer;
use App\Services\Test\QuestionGeneratorService;
use Illuminate\Support\Facades\DB;

class UserTestAttemptService
{
    public function __construct(
        protected QuestionGeneratorService $questionGenerator
    ) {}

    /**
     * Start a test: create attempt, generate questions, save to DB. Returns attempt + questions for UI.
     */
    public function startTest(User $user, int $userPackageId, int $testId): array
    {
        $userPackage = UserPackage::with('package')
            ->where('user_id', $user->id)
            ->where('id', $userPackageId)
            ->where('status', 'active')
            ->where('expiry_date', '>', now())
            ->firstOrFail();

        $test = Test::with('testSections.section')->findOrFail($testId);

        $packageTestIds = $userPackage->package->tests()->pluck('id')->toArray();
        if (!in_array($testId, $packageTestIds, true)) {
            throw new \InvalidArgumentException('This test is not part of your package.');
        }

        $attempt = UserTestAttempt::where('user_id', $user->id)
            ->where('user_package_id', $userPackageId)
            ->where('test_id', $testId)
            ->where('status', UserTestAttempt::STATUS_IN_PROGRESS)
            ->first();

        if ($attempt) {
            return $this->attemptResponse($attempt);
        }

        $questionIds = $this->questionGenerator->generate($test);

        return DB::transaction(function () use ($user, $userPackageId, $testId, $test, $questionIds) {
            $attempt = UserTestAttempt::create([
                'user_id' => $user->id,
                'user_package_id' => $userPackageId,
                'test_id' => $testId,
                'started_at' => now(),
                'status' => UserTestAttempt::STATUS_IN_PROGRESS,
            ]);

            $questions = Question::with('options:id,question_id,option_text,sequence')
                ->whereIn('id', $questionIds)
                ->get()
                ->keyBy('id');

            $order = array_values($questionIds);
            foreach ($order as $seq => $qid) {
                $q = $questions->get($qid);
                if (!$q) continue;
                UserTestAttemptQuestion::create([
                    'user_test_attempt_id' => $attempt->id,
                    'question_id' => $q->id,
                    'section_id' => $q->section_id,
                    'sequence' => $seq + 1,
                ]);
            }

            return $this->attemptResponse($attempt->fresh('attemptQuestions.question.options'));
        });
    }

    /**
     * Submit single answer for a question in the attempt.
     */
    public function submitAnswer(User $user, int $attemptId, int $questionId, ?int $questionOptionId = null, ?string $answerValue = null): array
    {
        $attempt = UserTestAttempt::with('attemptQuestions.question.options')
            ->where('id', $attemptId)
            ->where('user_id', $user->id)
            ->where('status', UserTestAttempt::STATUS_IN_PROGRESS)
            ->firstOrFail();

        $attemptQuestion = $attempt->attemptQuestions->firstWhere('question_id', $questionId);
        if (!$attemptQuestion) {
            throw new \InvalidArgumentException('Question does not belong to this attempt.');
        }

        $question = $attemptQuestion->question;
        if (!$question) {
            throw new \InvalidArgumentException('Question not found.');
        }
        $isCorrect = null;

        if ($question->question_type === 'mcq' && $questionOptionId !== null) {
            $option = $question->options->firstWhere('id', $questionOptionId);
            $isCorrect = $option ? (bool) $option->is_correct : false;
        }

        UserTestAnswer::updateOrCreate(
            [
                'user_test_attempt_id' => $attemptId,
                'question_id' => $questionId,
            ],
            [
                'question_option_id' => $questionOptionId,
                'answer_value' => $answerValue,
                'is_correct' => $isCorrect,
                'answered_at' => now(),
            ]
        );

        $totalQuestions = $attempt->attemptQuestions->count();
        $answeredCount = UserTestAnswer::where('user_test_attempt_id', $attemptId)->count();
        $hasNext = $answeredCount < $totalQuestions;

        $nextQuestion = null;
        if ($hasNext) {
            $nextSeq = $attemptQuestion->sequence + 1;
            $next = $attempt->attemptQuestions->firstWhere('sequence', $nextSeq);
            if ($next) {
                $nextQuestion = $this->formatQuestionForUser($next->question);
                $nextQuestion['sequence'] = $next->sequence;
            }
        }

        return [
            'message' => 'Answer saved.',
            'answered_count' => $answeredCount,
            'total_questions' => $totalQuestions,
            'all_answered' => !$hasNext,
            'next_question' => $nextQuestion,
        ];
    }

    /**
     * Generate score and mark attempt completed. Returns section-wise and total result.
     */
    public function generateScore(User $user, int $attemptId): array
    {
        $attempt = UserTestAttempt::with(['attemptQuestions.section', 'test'])
            ->where('id', $attemptId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($attempt->status === UserTestAttempt::STATUS_COMPLETED) {
            return $this->resultResponse($attempt);
        }

        $attempt->update([
            'status' => UserTestAttempt::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $answers = UserTestAnswer::where('user_test_attempt_id', $attemptId)
            ->whereNotNull('is_correct')
            ->get()
            ->keyBy('question_id');

        $sectionWise = [];
        $totalCorrect = 0;
        $totalQuestions = 0;

        foreach ($attempt->attemptQuestions as $aq) {
            $sectionId = $aq->section_id;
            $sectionName = $aq->section->name ?? 'Section';
            if (!isset($sectionWise[$sectionId])) {
                $sectionWise[$sectionId] = [
                    'section_id' => $sectionId,
                    'section_name' => $sectionName,
                    'correct' => 0,
                    'total' => 0,
                ];
            }
            $sectionWise[$sectionId]['total']++;
            $totalQuestions++;
            $ans = $answers->get($aq->question_id);
            if ($ans && $ans->is_correct) {
                $sectionWise[$sectionId]['correct']++;
                $totalCorrect++;
            }
        }

        return [
            'message' => 'Result generated.',
            'attempt_id' => $attempt->id,
            'test_id' => $attempt->test_id,
            'test_title' => $attempt->test->title ?? '',
            'status' => UserTestAttempt::STATUS_COMPLETED,
            'completed_at' => $attempt->completed_at->toIso8601String(),
            'section_wise' => array_values($sectionWise),
            'total_correct' => $totalCorrect,
            'total_questions' => $totalQuestions,
            'score_percentage' => $totalQuestions > 0 ? round(($totalCorrect / $totalQuestions) * 100, 2) : 0,
        ];
    }

    /**
     * List user's attempts (in_progress first, then completed).
     */
    public function listAttempts(User $user): array
    {
        $attempts = UserTestAttempt::with(['test', 'userPackage.package'])
            ->withCount(['attemptQuestions', 'answers'])
            ->where('user_id', $user->id)
            ->orderByRaw("status = 'in_progress' DESC")
            ->orderByDesc('started_at')
            ->get();

        $list = $attempts->map(function (UserTestAttempt $a) {
            return [
                'attempt_id' => $a->id,
                'test_id' => $a->test_id,
                'test_title' => $a->test->title ?? '',
                'package_name' => $a->userPackage->package->name ?? '',
                'status' => $a->status,
                'started_at' => $a->started_at->toIso8601String(),
                'completed_at' => $a->completed_at?->toIso8601String(),
                'total_questions' => $a->attempt_questions_count ?? 0,
                'answered_count' => $a->answers_count ?? 0,
            ];
        })->values()->toArray();

        return [
            'message' => 'My attempts.',
            'data' => $list,
        ];
    }

    /**
     * Get attempt with questions (e.g. resume).
     */
    public function getAttempt(User $user, int $attemptId): array
    {
        $attempt = UserTestAttempt::with(['attemptQuestions.question.options', 'attemptQuestions.section', 'test'])
            ->where('id', $attemptId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($attempt->isCompleted()) {
            return $this->resultResponse($attempt);
        }

        return $this->attemptResponse($attempt);
    }

    protected function attemptResponse(UserTestAttempt $attempt): array
    {
        $attempt->load(['attemptQuestions.question.options', 'test']);
        $questions = $attempt->attemptQuestions->sortBy('sequence')->values()->map(function ($aq, $index) {
            $q = $this->formatQuestionForUser($aq->question);
            $q['sequence'] = $aq->sequence;
            return $q;
        });

        return [
            'message' => 'Test started successfully.',
            'attempt_id' => $attempt->id,
            'test_id' => $attempt->test_id,
            'test_title' => $attempt->test->title ?? '',
            'total_time' => $attempt->test->total_time ?? 0,
            'started_at' => $attempt->started_at->toIso8601String(),
            'status' => $attempt->status,
            'total_questions' => $attempt->attemptQuestions->count(),
            'questions' => $questions->values()->toArray(),
        ];
    }

    protected function formatQuestionForUser($question): array
    {
        $options = $question->options
            ->sortBy('sequence')
            ->values()
            ->map(fn ($opt) => [
                'id' => $opt->id,
                'text' => $opt->option_text,
                'sequence' => $opt->sequence,
            ]);
        return [
            'id' => $question->id,
            'section_id' => $question->section_id,
            'question' => $question->question_text,
            'type' => $question->question_type,
            'difficulty' => $question->difficulty,
            'options' => $options->toArray(),
        ];
    }

    protected function resultResponse(UserTestAttempt $attempt): array
    {
        $answers = UserTestAnswer::where('user_test_attempt_id', $attempt->id)
            ->whereNotNull('is_correct')
            ->get()
            ->keyBy('question_id');

        $sectionWise = [];
        $totalCorrect = 0;
        $totalQuestions = 0;
        foreach ($attempt->attemptQuestions as $aq) {
            $sid = $aq->section_id;
            $name = $aq->section->name ?? 'Section';
            if (!isset($sectionWise[$sid])) {
                $sectionWise[$sid] = [
                    'section_id' => $sid,
                    'section_name' => $name,
                    'correct' => 0,
                    'total' => 0,
                ];
            }
            $sectionWise[$sid]['total']++;
            $totalQuestions++;
            if ($answers->get($aq->question_id)?->is_correct) {
                $sectionWise[$sid]['correct']++;
                $totalCorrect++;
            }
        }

        return [
            'message' => 'Result generated.',
            'attempt_id' => $attempt->id,
            'test_id' => $attempt->test_id,
            'test_title' => $attempt->test->title ?? '',
            'status' => $attempt->status,
            'completed_at' => $attempt->completed_at?->toIso8601String(),
            'section_wise' => array_values($sectionWise),
            'total_correct' => $totalCorrect,
            'total_questions' => $totalQuestions,
            'score_percentage' => $totalQuestions > 0 ? round(($totalCorrect / $totalQuestions) * 100, 2) : 0,
        ];
    }
}
