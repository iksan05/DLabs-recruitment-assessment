<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        UserRole::create(['role_name' => 'Admin']);
        UserRole::create(['role_name' => 'User']);
    }

    protected function getRandomRole()
    {
        $roles = UserRole::pluck('role_name')->toArray();
        return $roles[array_rand($roles)];
    }

    public function testRegister()
    {
        $randomRole = $this->getRandomRole();
        $role = UserRole::where('role_name', $randomRole)->first();
        $response = $this
            ->postJson(
                '/api/register',
                [
                    'nama' => 'John Doe',
                    'email' => 'john.doe@example.com',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                    'umur' => 25,
                    'status_anggota' => rand(0, 1),
                    'role_id' => $role->id
                ]
            );
        $response->assertStatus(200)->assertJsonStructure(['success', 'user' => ['id', 'nama', 'email', 'umur', 'status_anggota', 'created_at', 'updated_at'], 'token']);
        $userId = $response['user']['id'];
        $this->assertNotNull($userId, "User ID should not be null");
        $this->assertDatabaseHas('users', ['id' => $userId, 'nama' => 'John Doe', 'email' => 'john.doe@example.com',]);
        $this->assertDatabaseHas('user_role_mappings', ['user_id' => $userId, 'role_id' => $role->id,]);
    }

    #[DataProvider('validationErrorProvider')]
    public function testRegisterWithValidationErrors($data, $expectedErrors)
    {
        if (in_array('email', $expectedErrors)) {
            User::factory()->create(['email' => 'existing.user@example.com']);
        }
        $response = $this->postJson('/api/register', $data);
        $response->assertStatus(422);
        foreach ($expectedErrors as $field) {
            $response->assertJsonStructure([$field]);
        }
    }


    public static function validationErrorProvider()
    {

        return [
            'All fields empty' =>
            [[
                'nama' => '',
                'email' => '',
                'password' => '',
                'password_confirmation' => '',
                'umur' => null,
                'status_anggota' => null,
                'role_id' => null
            ], ['nama', 'email', 'password', 'role_id']],
            'Missing email' =>
            [[
                'nama' => 'John Doe',
                'email' => '',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'umur' => 25,
                'status_anggota' => 1,
                'role_id' => 1
            ], ['email']],
            'Missing password' =>
            [[
                'nama' => 'John Doe',
                'email' => 'john.doe@example.com',
                'password' => '',
                'password_confirmation' => '',
                'umur' => 25,
                'status_anggota' => 1,
                'role_id' => 1
            ], ['password']],
            'Negative age' =>
            [[
                'nama' => 'John Doe',
                'email' => 'john.doe@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'umur' => -5,
                'status_anggota' => 1,
                'role_id' => 1
            ], ['umur']],
            'Email already taken' =>
            [[
                'nama' => 'Jane Doe',
                'email' => 'existing.user@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'umur' => 30,
                'status_anggota' => 1,
                'role_id' => 1
            ], ['email']],
            'Invalid email format' =>
            [[
                'nama' => 'John Doe',
                'email' => 'invalid-email',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'umur' => 25,
                'status_anggota' => 1,
                'role_id' => 1
            ], ['email']]
        ];
    }
}
