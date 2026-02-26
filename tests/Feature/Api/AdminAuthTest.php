<?php

namespace Tests\Feature\Api;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Models\Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => null, 'is_active' => true]
        );
    }

    public function test_admin_can_register_and_receive_token(): void
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);

        $response = $this->postJson('/api/admin/auth/register', [
            'first_name' => 'Admin',
            'last_name' => 'One',
            'mobile' => '9999999999',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_name' => 'tests',
            'role_id' => $role->id,
        ]);

        $response->assertCreated();
        $response->assertJsonStructure([
            'message',
            'data' => [
                'admin' => ['id', 'first_name', 'last_name', 'email', 'mobile', 'status'],
                'token',
                'token_type',
            ],
        ]);

        $this->assertDatabaseHas('admins', ['email' => 'admin@example.com']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_admin_can_login_and_access_admin_me(): void
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $permission1 = Permission::create(['name' => 'Create Package', 'key' => 'package.create', 'group' => 'package']);
        $permission2 = Permission::create(['name' => 'Update Package', 'key' => 'package.update', 'group' => 'package']);
        $role->permissions()->attach([$permission1->id, $permission2->id]);

        $admin = Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $login = $this->postJson('/api/admin/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
            'device_name' => 'tests',
        ])->assertOk();

        $token = $login->json('data.token');

        $me = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/admin/me');

        $me->assertOk();
        $me->assertJsonFragment([
            'id' => $admin->id,
            'email' => $admin->email,
        ]);
    }

    public function test_user_token_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $login = $this->postJson('/api/user/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
            'device_name' => 'tests',
        ])->assertOk();

        $token = $login->json('data.token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/admin/me');

        $response->assertStatus(403);
    }

    public function test_admin_token_cannot_access_user_routes(): void
    {
        $admin = Admin::factory()->create([
            'password' => 'password123',
            'status' => 'active',
        ]);

        $login = $this->postJson('/api/admin/auth/login', [
            'email' => $admin->email,
            'password' => 'password123',
            'device_name' => 'tests',
        ])->assertOk();

        $token = $login->json('data.token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/user/init');

        $response->assertStatus(403);
    }

    public function test_admin_can_logout_and_token_is_revoked(): void
    {
        $admin = Admin::factory()->create([
            'password' => 'password123',
            'status' => 'active',
        ]);

        $login = $this->postJson('/api/admin/auth/login', [
            'email' => $admin->email,
            'password' => 'password123',
            'device_name' => 'tests',
        ])->assertOk();

        $token = $login->json('data.token');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $logout = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/admin/auth/logout');

        $logout->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertNull(PersonalAccessToken::findToken($token));
    }
}

