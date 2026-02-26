<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Admin;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $token;
    protected $section;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Role::create(['name' => 'Admin', 'slug' => 'admin', 'is_active' => true]);

        $this->admin = Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/admin/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
            'device_name' => 'tests',
        ]);

        $this->token = $response->json('data.token');
        $this->section = Section::factory()->create();
    }

    public function test_admin_can_list_questions(): void
    {
        Question::factory()->count(3)->create(['section_id' => $this->section->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/admin/test/questions?section_id=' . $this->section->id);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'question_text', 'question_type', 'difficulty', 'status', 'options'],
            ],
        ]);
    }

    public function test_admin_can_create_question_with_options(): void
    {
        $payload = [
            'section_id' => $this->section->id,
            'question_text' => 'What is 2+2?',
            'question_type' => 'mcq',
            'difficulty' => 'easy',
            'options' => [
                ['option_text' => '3', 'is_correct' => false, 'sequence' => 1, 'score_value' => 0],
                ['option_text' => '4', 'is_correct' => true, 'sequence' => 2, 'score_value' => 1],
                ['option_text' => '5', 'is_correct' => false, 'sequence' => 3, 'score_value' => 0],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/admin/test/questions', $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('questions', ['question_text' => 'What is 2+2?']);
        $this->assertDatabaseHas('question_options', ['option_text' => '4', 'is_correct' => 1]);
    }

    public function test_admin_can_view_single_question(): void
    {
        $question = Question::factory()->create(['section_id' => $this->section->id]);
        QuestionOption::factory()->count(2)->create(['question_id' => $question->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/admin/test/questions/{$question->id}");

        $response->assertOk();
        $response->assertJsonFragment(['id' => $question->id]);
        $response->assertJsonCount(2, 'data.options');
    }

    public function test_admin_can_update_question(): void
    {
        $question = Question::factory()->create(['section_id' => $this->section->id]);
        QuestionOption::factory()->create(['question_id' => $question->id, 'option_text' => 'Old Option']);

        $payload = [
            'section_id' => $this->section->id,
            'question_text' => 'Updated Question Text',
            'difficulty' => 'medium',
            'status' => true,
            'options' => [
                ['option_text' => 'New Option 1', 'is_correct' => true, 'sequence' => 1, 'score_value' => 5],
                ['option_text' => 'New Option 2', 'is_correct' => false, 'sequence' => 2, 'score_value' => 0],
            ],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/admin/test/questions/{$question->id}", $payload);

        $response->assertOk();
        $this->assertDatabaseHas('questions', ['id' => $question->id, 'question_text' => 'Updated Question Text']);
        // Verify old options are deleted (or at least replaced) and new ones exist
        $this->assertDatabaseHas('question_options', ['question_id' => $question->id, 'option_text' => 'New Option 1']);
        $this->assertDatabaseMissing('question_options', ['question_id' => $question->id, 'option_text' => 'Old Option']);
    }

    public function test_admin_can_delete_question(): void
    {
        $question = Question::factory()->create(['section_id' => $this->section->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/admin/test/questions/{$question->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }
}
