<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_own_profile(): void
    {
        $user = User::create([
            'name' => 'Original',
            'email' => 'user1@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '111111111',
            'balance' => 0,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/users/' . $user->id, [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'name' => 'Updated Name',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_user_cannot_update_other_user_profile(): void
    {
        $user = User::create([
            'name' => 'User',
            'email' => 'user2@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '222222222',
            'balance' => 0,
        ]);

        // ensure this user is not admin
        $user->update(['is_admin' => false]);

        $other = User::create([
            'name' => 'Other',
            'email' => 'other@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '333333333',
            'balance' => 0,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/users/' . $other->id, [
                'name' => 'Hacked',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('users', [
            'id' => $other->id,
            'name' => 'Hacked',
        ]);
    }

    public function test_admin_can_update_other_user_profile(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '444444444',
            'balance' => 0,
            'is_admin' => true,
        ]);

        $target = User::create([
            'name' => 'Target',
            'email' => 'target@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '555555555',
            'balance' => 0,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson('/api/users/' . $target->id, [
                'name' => 'Admin Updated',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'name' => 'Admin Updated',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'Admin Updated',
        ]);
    }
}

