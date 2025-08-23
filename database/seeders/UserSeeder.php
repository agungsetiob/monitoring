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
        // Hapus semua pengguna yang ada
        User::create([
            'name' => 'Admin',
            'email' => 'admin@dhaan.com',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
        ]);
    }
}
