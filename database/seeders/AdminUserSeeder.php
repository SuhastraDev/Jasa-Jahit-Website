<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        User::firstOrCreate(
            ['email' => 'admin@zrinttailor.com'],
            [
                'name'              => 'Admin ZrintTailor',
                'password'          => Hash::make('password123'),
                'phone'             => '081234567890',
                'role'              => 'admin',
                'address'           => 'Yogyakarta',
                'email_verified_at' => now(),
            ]
        );

        // Demo pelanggan untuk ujicoba
        User::firstOrCreate(
            ['email' => 'demo@zrinttailor.com'],
            [
                'name'              => 'Demo Pelanggan',
                'password'          => Hash::make('password123'),
                'phone'             => '082233445566',
                'role'              => 'user',
                'address'           => 'Jl. Contoh No. 5, Yogyakarta',
                'email_verified_at' => now(),
            ]
        );
    }
}
