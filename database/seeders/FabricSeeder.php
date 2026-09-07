<?php

namespace Database\Seeders;

use App\Models\Fabric;
use Illuminate\Database\Seeder;

class FabricSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fabrics = [
            ['name' => 'Katun',      'price_addition' => 15000, 'stock_meters' => 120],
            ['name' => 'Linen',      'price_addition' => 35000, 'stock_meters' => 60],
            ['name' => 'Silk',       'price_addition' => 75000, 'stock_meters' => 25],
            ['name' => 'Sifon',      'price_addition' => 25000, 'stock_meters' => 80],
            ['name' => 'Brokat',     'price_addition' => 90000, 'stock_meters' => 15],
            ['name' => 'Jersey',     'price_addition' => 20000, 'stock_meters' => 100],
            ['name' => 'Wool',       'price_addition' => 60000, 'stock_meters' => 30],
            ['name' => 'Polyester',  'price_addition' => 10000, 'stock_meters' => 150],
        ];

        foreach ($fabrics as $fabric) {
            Fabric::updateOrCreate(
                ['name' => $fabric['name']],
                [
                    'price_addition' => $fabric['price_addition'],
                    'stock_meters'   => $fabric['stock_meters'],
                    'is_active'      => true,
                ]
            );
        }
    }
}
