<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // User Admin
        User::create([
            'name' => 'Admin Utama',
            'email' => 'admin@dhaan.com',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
        ]);

        // User IGD
        User::create([
            'name' => 'Petugas IGD',
            'email' => 'igd@dhaan.com',
            'password' => Hash::make('12345678'),
            'role' => 'igd',
        ]);

        // User Apotek
        User::create([
            'name' => 'Petugas Apotek',
            'email' => 'apotek@dhaan.com',
            'password' => Hash::make('12345678'),
            'role' => 'apotek',
        ]);
    }
}