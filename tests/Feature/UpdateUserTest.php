<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class UpdateUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testUpdateUserSuccessfully()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        $updateData = [
            'nama' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->putJson("/api/users/{$user->id}", $updateData, ['Authorization' => "Bearer $token"]);
        $response->assertStatus(200)->assertJson([
                'message' => 'User updated successfully',
                'user' => ['id' => $user->id, 'nama' => 'Updated Name', 'email' => 'updated@example.com',],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'nama' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function testUpdateUserUnauthorized()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $token = JWTAuth::fromUser($anotherUser);
        $updateData = [
            'nama' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->putJson("/api/users/{$user->id}", $updateData, ['Authorization' => "Bearer $token"]);

        $response->assertStatus(403)->assertJson([
            'message' => 'Unauthorized',
        ]);
    }

    public function testUpdateUserNotFound()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        $nonExistentUserId = 999;
        $updateData = [
            'nama' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->putJson("/api/users/{$nonExistentUserId}", $updateData, ['Authorization' => "Bearer $token"]);

        $response->assertStatus(404)->assertJson([
            'message' => 'User not found',
        ]);
    }
}
