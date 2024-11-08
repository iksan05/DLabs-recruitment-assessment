<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DeleteUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        UserRole::create(['role_name' => 'Admin']);
        UserRole::create(['role_name' => 'User']);
    }

    protected function createUserWithRole($roleName)
    {
        $role = UserRole::where('role_name', $roleName)->first();
        $user = User::factory()->create();
        $user->roles()->attach($role->id);
        return $user;
    }

    public function testDestroyUserSuccessfully()
    {
        $adminUser = $this->createUserWithRole('Admin');
        $token = JWTAuth::fromUser($adminUser);
        $user = User::factory()->create();
        $response = $this->deleteJson("/api/users/{$user->id}", [], ['Authorization' => "Bearer $token"]);
        $response->assertStatus(200)->assertJson([
            'message' => 'User deleted successfully',
        ]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function testDestroyUserUnauthorized()
    {
        $regularUser = $this->createUserWithRole('User');
        $token = JWTAuth::fromUser($regularUser);
        $user = User::factory()->create();
        $response = $this->deleteJson("/api/users/{$user->id}", [], ['Authorization' => "Bearer $token"]);
        $response->assertStatus(403)->assertJson([
            'message' => 'Unauthorized',
        ]);
    }

    public function testDestroyUserNotFound()
    {
        $adminUser = $this->createUserWithRole('Admin');
        $token = JWTAuth::fromUser($adminUser);
        $nonExistentUserId = 999;
        $response = $this->deleteJson("/api/users/{$nonExistentUserId}", [], ['Authorization' => "Bearer $token"]);
        $response->assertStatus(404)->assertJson([
            'message' => 'User not found',
        ]);
    }
}
