<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserControllerTest extends TestCase
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

    #[DataProvider('paginationProvider')]
    public function testIndex($page, $perPage)
    {
        User::factory()->count(30)->create();
        $page = (int) $page;
        $perPage = (int) $perPage;
        $response = $this->getJson("/api/users?page={$page}&per_page={$perPage}");
        $response->assertStatus(200)->assertJsonStructure(['users' => ['data' => ['*' => ['id', 'nama', 'email', 'umur', 'status_anggota', 'created_at', 'updated_at',]], 'links']]);
    }

    public static function paginationProvider()
    {
        return ['Page 1, 10 per page' => [1, 10], 'Page 2, 5 per page' => [2, 5], 'Page 3, 15 per page' => [3, 15],];
    }
}
