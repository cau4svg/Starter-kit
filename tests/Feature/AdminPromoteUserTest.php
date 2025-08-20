<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPromoteUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_promote_user_to_admin(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '123456789',
            'balance' => 0,
        ]);

        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '987654321',
            'balance' => 0,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/users/'.$user->id.'/make-admin');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Usuário promovido a administrador com sucesso',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_admin' => true,
        ]);
    }

    public function test_non_admin_cannot_promote_user_to_admin(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin2@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '111111111',
            'balance' => 0,
        ]);

        $nonAdmin = User::create([
            'name' => 'NonAdmin',
            'email' => 'nonadmin@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '222222222',
            'balance' => 0,
        ]);

        $target = User::create([
            'name' => 'Target',
            'email' => 'target@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '333333333',
            'balance' => 0,
        ]);

        $response = $this->actingAs($nonAdmin, 'sanctum')
            ->postJson('/api/users/'.$target->id.'/make-admin');

        $response->assertStatus(403);

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_admin' => false,
        ]);
    }
}
