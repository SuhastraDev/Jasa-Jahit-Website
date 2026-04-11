<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\Catalog;
use Illuminate\Support\Str;

class Phase3Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'name'           => 'Permak',
                'slug'           => 'permak',
                'description'    => 'Modifikasi atau perbaikan pakaian yang sudah jadi: potong, kecilkan, besarkan, atau perbaiki jahitan.',
                'base_price'     => 30000,
                'estimated_days' => 3,
                'is_active'      => true,
            ],
            [
                'name'           => 'Desain',
                'slug'           => 'desain',
                'description'    => 'Konsultasi dan pembuatan desain pakaian baru berdasarkan referensi atau katalog pilihan.',
                'base_price'     => 150000,
                'estimated_days' => 7,
                'is_active'      => true,
            ],
            [
                'name'           => 'Custom',
                'slug'           => 'custom',
                'description'    => 'Pembuatan pakaian dari nol sesuai keinginan pelanggan. Wajib menggunakan fitur CV ukuran badan.',
                'base_price'     => 300000,
                'estimated_days' => 14,
                'is_active'      => true,
            ],
        ];

        foreach ($services as $serviceData) {
            $service = Service::create($serviceData);

            // Buat contoh katalog untuk layanan Desain dan Custom
            if (in_array($service->name, ['Desain', 'Custom'])) {
                for ($i = 1; $i <= 3; $i++) {
                    Catalog::create([
                        'service_id'  => $service->id,
                        'name'        => 'Contoh ' . $service->name . ' Tipe ' . $i,
                        'description' => 'Referensi desain ' . strtolower($service->name) . ' tipe ' . $i . '. Diskusikan detail dengan admin via chat.',
                        'image_path'  => null,
                        'is_active'   => true,
                    ]);
                }
            }
        }
    }
}
