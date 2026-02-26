<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Admin;
use App\Models\Section;
use App\Models\Question; // Assuming Question model exists based on controller check
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $token;

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
    }

    public function test_admin_can_list_sections(): void
    {
        Section::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/admin/test/sections');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'description', 'status', 'created_at', 'updated_at'],
            ],
        ]);
    }

    public function test_admin_can_create_section(): void
    {
        $payload = [
            'name' => 'Logical Reasoning',
            'description' => 'Test your logic',
            'status' => true,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/admin/test/sections', $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('sections', ['name' => 'Logical Reasoning']);
    }

    public function test_admin_cannot_create_duplicate_section_name(): void
    {
        Section::factory()->create(['name' => 'Duplicate Name']);

        $payload = [
            'name' => 'Duplicate Name',
            'description' => 'Another one',
            'status' => true,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/admin/test/sections', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_admin_can_view_single_section(): void
    {
        $section = Section::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/admin/test/sections/{$section->id}");

        $response->assertOk();
        $response->assertJsonFragment(['id' => $section->id]);
    }

    public function test_admin_can_update_section(): void
    {
        $section = Section::factory()->create();

        $payload = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'status' => false,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/admin/test/sections/{$section->id}", $payload);

        $response->assertOk();
        $this->assertDatabaseHas('sections', ['id' => $section->id, 'name' => 'Updated Name']);
    }

    public function test_admin_can_delete_section(): void
    {
        $section = Section::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/admin/test/sections/{$section->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
    }
}
