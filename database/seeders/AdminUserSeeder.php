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
        User::create([
            'name' => 'Admin ZrintTailor',
            'email' => 'admin@zrinttailor.com',
            'password' => Hash::make('password123'),
            'phone' => '081234567890',
            'role' => 'admin',
            'address' => 'Jl. Admin Raya No. 1'
        ]);
    }
}
