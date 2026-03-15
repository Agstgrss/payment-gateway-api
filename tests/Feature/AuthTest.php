<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        User::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
            'role' => 'USER',
        ]);
    }

    public function test_can_login_with_valid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token'
        ]);
    }

    public function test_cannot_login_with_invalid_password()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@test.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Invalid credentials');
    }

    public function test_cannot_login_with_nonexistent_user()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }

    public function test_cannot_login_without_email()
    {
        $response = $this->postJson('/api/login', [
            'password' => 'password',
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_login_without_password()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@test.com',
        ]);

        $response->assertStatus(422);
    }
}
