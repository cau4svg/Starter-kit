<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserUpdateEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_update_email_to_existing_email(): void
    {
        $user1 = User::create([
            'name' => 'User1',
            'email' => 'user1@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '111111111',
            'balance' => 0,
        ]);

        $user2 = User::create([
            'name' => 'User2',
            'email' => 'user2@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '222222222',
            'balance' => 0,
        ]);

        $response = $this->actingAs($user2, 'sanctum')
            ->putJson('/api/users/' . $user2->id, [
                'email' => 'user1@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'E-mail já está em uso',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user2->id,
            'email' => 'user2@example.com',
        ]);
    }
}
