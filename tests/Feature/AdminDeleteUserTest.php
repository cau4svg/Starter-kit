<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDeleteUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_user(): void
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
            ->deleteJson('/api/users/'.$user->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Usuário deletado com sucesso',
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_non_admin_cannot_delete_user(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin2@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '111111111',
            'balance' => 0,
        ]);

        $target = User::create([
            'name' => 'Target',
            'email' => 'target@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '222222222',
            'balance' => 0,
        ]);

        $nonAdmin = User::create([
            'name' => 'NonAdmin',
            'email' => 'nonadmin@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '333333333',
            'balance' => 0,
        ]);

        $response = $this->actingAs($nonAdmin, 'sanctum')
            ->deleteJson('/api/users/'.$target->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
        ]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin3@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '444444444',
            'balance' => 0,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/users/'.$admin->id);

        $response->assertStatus(400);

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
        ]);
    }
}
