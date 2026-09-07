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
        // Foto referensi diambil dari Pexels (free-to-use license) dan
        // disimpan di storage/app/public/clothing-types, di-commit ke repo
        // supaya ikut ter-deploy tanpa perlu upload manual dari admin.
        $types = [
            ['name' => 'Kemeja', 'gender' => 'unisex', 'reference_image' => 'clothing-types/kemeja.jpg'],
            ['name' => 'Baju Dinas', 'gender' => 'unisex', 'reference_image' => 'clothing-types/baju-dinas.jpg'],
            ['name' => 'Baju Sekolah', 'gender' => 'unisex', 'reference_image' => 'clothing-types/baju-sekolah.jpg'],
            ['name' => 'Celana Kain', 'gender' => 'unisex', 'reference_image' => 'clothing-types/celana-kain.jpg'],
            ['name' => 'Baju Koko', 'gender' => 'pria', 'reference_image' => 'clothing-types/baju-koko.jpg'],
            ['name' => 'Kebaya', 'gender' => 'wanita', 'reference_image' => 'clothing-types/kebaya.jpg'],
            ['name' => 'Gamis', 'gender' => 'wanita', 'reference_image' => 'clothing-types/gamis.jpg'],
            ['name' => 'Rok Kain', 'gender' => 'wanita', 'reference_image' => 'clothing-types/rok-kain.jpg'],
        ];

        foreach ($types as $type) {
            ClothingTypeReference::updateOrCreate(
                ['name' => $type['name']],
                [
                    'gender'          => $type['gender'],
                    'reference_image' => $type['reference_image'],
                    'is_active'       => true,
                ]
            );
        }
    }
}
