<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Admin;
use App\Models\Package;
use App\Models\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageTest extends TestCase
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

    public function test_admin_can_list_packages(): void
    {
        Package::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/admin/packages');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'slug', 'price', 'status', 'tests'],
            ],
        ]);
    }

    public function test_admin_can_create_package_with_tests(): void
    {
        $test1 = Test::factory()->create();
        $test2 = Test::factory()->create();

        $payload = [
            'name' => 'Premium Package',
            'description' => 'Access to all tests',
            'price' => 999.00,
            'validity_days' => 365,
            'attempt_limit' => 5,
            'status' => 1,
            'test_ids' => [$test1->id, $test2->id],
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/admin/packages', $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('packages', ['name' => 'Premium Package', 'price' => 999.00]);
        
        $package = Package::where('name', 'Premium Package')->first();
        $this->assertCount(2, $package->tests);
    }

    public function test_admin_can_view_single_package(): void
    {
        $package = Package::factory()->create();
        $tests = Test::factory()->count(2)->create();
        $package->tests()->attach($tests->pluck('id'));

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/admin/packages/{$package->id}");

        $response->assertOk();
        $response->assertJsonFragment(['id' => $package->id]);
        $response->assertJsonCount(2, 'data.tests');
    }

    public function test_admin_can_update_package(): void
    {
        $package = Package::factory()->create();
        $oldTests = Test::factory()->count(2)->create();
        $package->tests()->attach($oldTests->pluck('id'));

        $newTest = Test::factory()->create();

        $payload = [
            'name' => 'Updated Package Name',
            'price' => 499.50,
            'test_ids' => [$newTest->id], // Replacing old tests
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/admin/packages/{$package->id}", $payload);

        $response->assertOk();
        $this->assertDatabaseHas('packages', ['id' => $package->id, 'name' => 'Updated Package Name']);
        
        $package->refresh();
        $this->assertCount(1, $package->tests);
        $this->assertEquals($newTest->id, $package->tests->first()->id);
    }

    public function test_admin_can_delete_package(): void
    {
        $package = Package::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/admin/packages/{$package->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('packages', ['id' => $package->id]);
    }
}
