<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_user_can_login_and_view_profile_but_cannot_operate(): void
    {
        $user = User::factory()->create([
            'password' => 'Secret123!',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Secret123!',
        ]);

        $token = $login->assertOk()
            ->assertJsonPath('user.estado', User::STATUS_PENDING)
            ->json('token');

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.estado', User::STATUS_PENDING);

        $this->withToken($token)
            ->getJson('/api/shared/ping')
            ->assertForbidden()
            ->assertJsonPath('code', 'ACCOUNT_PENDING');
    }

    public function test_disabled_user_cannot_login(): void
    {
        $user = User::factory()->disabled()->create([
            'password' => 'Secret123!',
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Secret123!',
        ])
            ->assertForbidden()
            ->assertJsonPath('code', 'ACCOUNT_DISABLED');
    }

    public function test_disabling_user_invalidates_an_open_session_on_next_operation(): void
    {
        $user = User::factory()->active()->create([
            'password' => 'Secret123!',
        ]);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'Secret123!',
        ])->json('token');

        $user->update([
            'active' => false,
            'estado' => User::STATUS_DISABLED,
        ]);

        $this->withToken($token)
            ->getJson('/api/shared/ping')
            ->assertForbidden()
            ->assertJsonPath('code', 'ACCOUNT_DISABLED');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_active_seller_can_use_shared_routes_but_not_admin_routes(): void
    {
        $user = User::factory()->active()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/shared/ping')
            ->assertOk();

        $this->withToken($token)
            ->getJson('/api/admin/dashboard')
            ->assertForbidden()
            ->assertJsonPath('code', 'INSUFFICIENT_ROLE');
    }

    public function test_active_admin_can_use_admin_routes(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/admin/dashboard')
            ->assertOk();
    }

    public function test_unauthenticated_request_receives_json_unauthorized_response(): void
    {
        $this->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }
}
