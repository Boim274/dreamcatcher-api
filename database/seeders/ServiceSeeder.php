<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => 'Sablon Kaos',
                'description' => 'Sablon kaos berkualitas tinggi dengan teknik DTG dan screen print. Cocok untuk desain custom dengan warna full color atau limited edition.',
                'base_price' => 35000,
                'price_per_unit' => null,
                'minimum_order' => 12,
                'thumbnail' => null,
                'is_active' => true,
                'pricing_type' => 'tiered',
                'pricing_config' => json_encode([
                    'plastisol' => [
                        'lusin_1' => 65000,
                        'lusin_2_6' => 60000,
                        'satuan' => 75000,
                    ],
                    'dtf' => [
                        'satuan' => 80000,
                    ],
                ]),
                'options_config' => json_encode([
                    'colors' => ['Hitam', 'Putih', 'Abu-abu', 'Navy', 'Maroon', 'Mocca', 'Lime', 'Pink'],
                    'sablon_types' => ['Plastisol', 'DTF'],
                    'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
                    'size_pricing' => [
                        'S' => 0,
                        'M' => 0,
                        'L' => 0,
                        'XL' => 5000,
                        'XXL' => 10000,
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Printing Banner',
                'description' => 'Cetak banner dengan kualitas tinggi. Tersedia berbagai ukuran dan bahan seperti flexi chinch, vinyl, dan fabric.',
                'base_price' => 25000,
                'price_per_unit' => null,
                'minimum_order' => 1,
                'thumbnail' => null,
                'is_active' => true,
                'pricing_type' => 'flat',
                'pricing_config' => null,
                'options_config' => json_encode([
                    'sizes' => ['60x160cm', '80x200cm', '100x250cm'],
                    'materials' => ['Flexi Chinch', 'Vinyl', 'Fabric'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cetak Stiker',
                'description' => 'Cetak stiker custom untuk berbagai kebutuhan. Tersedia pilihan bahan vinyl glossy, matte, dan transparan.',
                'base_price' => 5000,
                'price_per_unit' => null,
                'minimum_order' => 50,
                'thumbnail' => null,
                'is_active' => true,
                'pricing_type' => 'flat',
                'pricing_config' => null,
                'options_config' => json_encode([
                    'materials' => ['Vinyl Glossy', 'Vinyl Matte', 'Transparan'],
                    'sizes' => ['5x5cm', '7x7cm', '10x10cm', 'Custom'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bordir',
                'description' => 'Bordir komputer dengan hasil rapi dan profesional. Cocok untuk seragam perusahaan, komunitas, atau hadiah.',
                'base_price' => 45000,
                'price_per_unit' => null,
                'minimum_order' => 6,
                'thumbnail' => null,
                'is_active' => true,
                'pricing_type' => 'flat',
                'pricing_config' => null,
                'options_config' => json_encode([
                    'positions' => ['Dada Kiri', 'Dada Kanan', 'Punggung', 'Lengan Kiri', 'Lengan Kanan'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('services')->insert($services);
    }
}
