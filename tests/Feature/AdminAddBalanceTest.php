<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAddBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_balance_to_non_admin_user(): void
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
            ->postJson('/api/users/' . $user->id . '/add-balance', [
                'amount' => 50,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Saldo adicionado com sucesso',
                'balance' => 50.0,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'balance' => 50.0,
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount' => 50.0,
            'type' => 'credit',
        ]);
    }

    public function test_non_admin_cannot_add_balance_to_other_user(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin2@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '123456789',
            'balance' => 0,
        ]);

        $target = User::create([
            'name' => 'Target',
            'email' => 'target@example.com',
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

        $response = $this->actingAs($nonAdmin, 'sanctum')
            ->postJson('/api/users/' . $target->id . '/add-balance', [
                'amount' => 50,
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('transactions', [
            'user_id' => $target->id,
            'amount' => 50.0,
        ]);
    }
}
