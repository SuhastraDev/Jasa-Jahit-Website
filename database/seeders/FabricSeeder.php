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
            ['name' => 'Katun',      'category' => 'baju',   'price_addition' => 15000, 'stock_meters' => 120],
            ['name' => 'Linen',      'category' => 'baju',   'price_addition' => 35000, 'stock_meters' => 60],
            ['name' => 'Silk',       'category' => 'baju',   'price_addition' => 75000, 'stock_meters' => 25],
            ['name' => 'Sifon',      'category' => 'baju',   'price_addition' => 25000, 'stock_meters' => 80],
            ['name' => 'Brokat',     'category' => 'baju',   'price_addition' => 90000, 'stock_meters' => 15],
            ['name' => 'Jersey',     'category' => 'baju',   'price_addition' => 20000, 'stock_meters' => 100],
            ['name' => 'Wool',       'category' => 'celana', 'price_addition' => 60000, 'stock_meters' => 30],
            ['name' => 'Polyester',  'category' => 'celana', 'price_addition' => 10000, 'stock_meters' => 150],
            ['name' => 'Katun Drill', 'category' => 'celana', 'price_addition' => 30000, 'stock_meters' => 90],
            ['name' => 'Twill',      'category' => 'celana', 'price_addition' => 40000, 'stock_meters' => 70],
            ['name' => 'Satin',      'category' => 'rok',    'price_addition' => 45000, 'stock_meters' => 40],
            ['name' => 'Rayon',      'category' => 'rok',    'price_addition' => 20000, 'stock_meters' => 85],
        ];

        foreach ($fabrics as $fabric) {
            Fabric::updateOrCreate(
                ['name' => $fabric['name'], 'category' => $fabric['category']],
                [
                    'price_addition' => $fabric['price_addition'],
                    'stock_meters'   => $fabric['stock_meters'],
                    'is_active'      => true,
                ]
            );
        }
    }
}
