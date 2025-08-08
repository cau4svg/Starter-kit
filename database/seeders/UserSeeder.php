<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            User::create([
            //'id' => Str::uuid("v4"),    
            'name' => "Artur",
            'email' => "Artur@apibrasil.com",
            'password' => Hash::make('Teste'),
            'balance' => 999999
        ]);
    }
}
