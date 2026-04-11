<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_get_token(): void
    {
        User::query()->create([
            'nama' => 'Admin Demo',
            'name' => 'Admin Demo',
            'email' => 'admin.demo@gmp.local',
            'password' => 'rahasia123',
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin.demo@gmp.local',
            'password' => 'rahasia123',
            'role' => 'admin',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'token',
                'token_type',
                'user' => ['id', 'nama', 'email', 'role'],
            ]);
    }

    public function test_role_mismatch_is_rejected(): void
    {
        User::query()->create([
            'nama' => 'Admin Demo',
            'name' => 'Admin Demo',
            'email' => 'admin.demo@gmp.local',
            'password' => 'rahasia123',
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin.demo@gmp.local',
            'password' => 'rahasia123',
            'role' => 'superadmin',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_authenticated_user_can_view_profile_and_logout(): void
    {
        $user = User::query()->create([
            'nama' => 'Admin Demo',
            'name' => 'Admin Demo',
            'email' => 'admin.demo@gmp.local',
            'password' => 'rahasia123',
            'role' => 'admin',
        ]);

        $plainToken = $user->issueApiToken();

        $headers = [
            'Authorization' => 'Bearer '.$plainToken,
            'Accept' => 'application/json',
        ];

        $this->getJson('/api/auth/me', $headers)
            ->assertOk()
            ->assertJsonPath('user.email', 'admin.demo@gmp.local');

        $this->postJson('/api/auth/logout', [], $headers)
            ->assertOk()
            ->assertJsonPath('message', 'Logout berhasil.');

        $this->getJson('/api/auth/me', $headers)
            ->assertUnauthorized();
    }
}
