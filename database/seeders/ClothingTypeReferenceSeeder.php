<?php

namespace Database\Seeders;

use App\Models\ClothingTypeReference;
use Illuminate\Database\Seeder;

class ClothingTypeReferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Kemeja', 'gender' => 'unisex'],
            ['name' => 'Baju Dinas', 'gender' => 'unisex'],
            ['name' => 'Baju Sekolah', 'gender' => 'unisex'],
            ['name' => 'Celana Kain', 'gender' => 'unisex'],
            ['name' => 'Baju Koko', 'gender' => 'pria'],
            ['name' => 'Kebaya', 'gender' => 'wanita'],
            ['name' => 'Gamis', 'gender' => 'wanita'],
            ['name' => 'Rok Kain', 'gender' => 'wanita'],
        ];

        foreach ($types as $type) {
            ClothingTypeReference::firstOrCreate(
                ['name' => $type['name']],
                ['gender' => $type['gender'], 'is_active' => true]
            );
        }
    }
}
