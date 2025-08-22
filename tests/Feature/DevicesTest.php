<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DevicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_device_token_proxies_response(): void
    {
        Http::fake([
            'https://gateway.apibrasil.io/api/v2/devices/store' => Http::response([
                'device' => ['device_token' => 'abc123'],
            ], 200),
        ]);

        $user = User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '123456789',
            'balance' => 0,
            'bearer_apibrasil' => 'token',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/devices/store', ['device_name' => 'teste']);

        $response->assertStatus(200)
            ->assertJson([
                'device' => ['device_token' => 'abc123'],
            ]);
    }

    public function test_index_returns_devices_from_api(): void
    {
        Http::fake([
            'https://gateway.apibrasil.io/api/v2/devices' => Http::response([
                'devices' => [],
            ], 200),
        ]);

        $user = User::create([
            'name' => 'User2',
            'email' => 'user2@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '987654321',
            'balance' => 0,
            'bearer_apibrasil' => 'token',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/devices');

        $response->assertStatus(200)
            ->assertJson([
                'devices' => [],
            ]);
    }

    public function test_search_updates_device(): void
    {
        Http::fake([
            'https://gateway.apibrasil.io/api/v2/devices/123/search' => Http::response([
                'device' => ['device_token' => '123'],
            ], 200),
        ]);

        $user = User::create([
            'name' => 'User3',
            'email' => 'user3@example.com',
            'password' => bcrypt('password'),
            'cellphone' => '555555555',
            'balance' => 0,
            'bearer_apibrasil' => 'token',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/devices/123/search');

        $response->assertStatus(200)
            ->assertJson([
                'device' => ['device_token' => '123'],
            ]);
    }
}

