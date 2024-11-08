<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoginTest extends TestCase
{

    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function testLogin()
    {
        $password = 'password123';
        $user = User::factory()->create(['password' => bcrypt($password)]);
        $response = $this
            ->postJson(
                '/api/login',
                [
                    'email' => $user->email,
                    'password' => $password,
                ]
            );
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'token'
            ]);

        $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrongpassword',]);
        $response->assertStatus(401)->assertJson(['success' => false, 'message' => 'Email atau Password Anda salah',]);
    }

    #[DataProvider('loginValidationProvider')] public function testLoginValidationErrors($data, $expectedErrors)
    {
        $response = $this->postJson('/api/login', $data);
        $response->assertStatus(422);
        foreach ($expectedErrors as $field) {
            $response->assertJsonStructure([$field]);
        }
    }
    public static function loginValidationProvider(): array
    {
        return
            [
                'Empty email and password' =>
                [
                    [
                        'email' => '',
                        'password' => '',
                    ],
                    ['email', 'password']
                ],
                'Empty email' =>
                [
                    [
                        'email' => '',
                        'password' => 'password123',
                    ],
                    ['email']
                ],
                'Empty password' =>
                [[
                    'email' => 'john.doe@example.com',
                    'password' => '',
                ], ['password']],
                'Invalid email format' =>
                [[
                    'email' => 'invalid-email',
                    'password' => 'password123',
                ], ['email']],
            ];
    }
}
