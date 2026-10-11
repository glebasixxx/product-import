<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_bearer_token(): void
    {
        User::factory()->create(['email' => 'admin@example.com', 'password' => 'secret123']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'name', 'email']]);

        $this->withToken($response->json('token'))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'admin@example.com');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'admin@example.com', 'password' => 'secret123']);

        $this->postJson('/api/auth/login', ['email' => 'admin@example.com', 'password' => 'wrong'])
            ->assertUnauthorized();
    }

    public function test_protected_routes_require_token(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
        $this->getJson('/api/products/1')->assertUnauthorized();
        $this->postJson('/api/imports')->assertUnauthorized();
        $this->get('/api/products')->assertUnauthorized();
    }

    public function test_logout_revokes_token(): void
    {
        User::factory()->create(['email' => 'admin@example.com', 'password' => 'secret123']);
        $token = $this->postJson('/api/auth/login', ['email' => 'admin@example.com', 'password' => 'secret123'])
            ->json('token');

        $this->withToken($token)->postJson('/api/auth/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
