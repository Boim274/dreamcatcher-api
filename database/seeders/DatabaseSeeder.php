<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@dreamcatcher.id'],
            [
                'name' => 'Admin Dreamcatcher',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'phone' => '081234567890',
            ]
        );

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('portfolios')->truncate();
        DB::table('services')->truncate();
        DB::table('colors')->truncate();
        DB::table('settings')->truncate();
        DB::table('testimonials')->truncate();
        DB::table('sizes')->truncate();
        DB::table('products')->truncate();
        DB::table('categories')->truncate();
        DB::table('product_images')->truncate();
        DB::table('product_sizes')->truncate();
        DB::table('product_colors')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $services = [
            [
                'name' => 'Sablon Kaos',
                'description' => 'Sablon kaos berkualitas tinggi dengan teknik DTG dan screen print. Cocok untuk desain custom dengan warna full color atau limited edition.',
                'base_price' => 35000,
                'price_per_unit' => null,
                'minimum_order' => 1,
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
                    'purchase_methods' => ['Satuan', 'Lusinan'],
                    'colors' => ['Hitam', 'Putih', 'Abu-abu', 'Navy', 'Merah', 'Maroon', 'Biru Royal', 'Biru Muda', 'Hijau Army', 'Hijau Botol', 'Kuning', 'Orange', 'Cream', 'Coklat', 'Ungu', 'Pink'],
                    'sablon_types' => ['Plastisol', 'DTF'],
                    'sizes' => ['S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL'],
                    'size_pricing' => [
                        'S' => 0,
                        'M' => 0,
                        'L' => 0,
                        'XL' => 0,
                        'XXL' => 5000,
                        '3XL' => 10000,
                        'XXXL' => 10000,
                        '4XL' => 20000,
                        'XXXXL' => 20000,
                    ],
                    'max_print_areas' => 2,
                    'area_surcharge' => 15000,
                    'print_positions' => [
                        ['label' => 'Standar Depan', 'value' => 'standar_depan'],
                        ['label' => 'Dada Tengah', 'value' => 'dada_tengah'],
                        ['label' => 'Dada Kiri', 'value' => 'dada_kiri'],
                        ['label' => 'Dada Kanan', 'value' => 'dada_kanan'],
                        ['label' => 'Standar Belakang', 'value' => 'standar_belakang'],
                        ['label' => 'Punggung Atas', 'value' => 'punggung_atas'],
                        ['label' => 'Lengan Kiri', 'value' => 'lengan_kiri'],
                        ['label' => 'Lengan Kanan', 'value' => 'lengan_kanan'],
                    ],
                    'print_sizes' => [
                        ['label' => '9 × 9 cm', 'value' => '9x9'],
                        ['label' => '9 × 28 cm', 'value' => '9x28'],
                        ['label' => '20 × 13 cm (A5)', 'value' => '20x13'],
                        ['label' => '28 × 20 cm (A4)', 'value' => '28x20'],
                        ['label' => '28 × 40 cm (A3)', 'value' => '28x40'],
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

        $sablonKaos = Service::query()->where('name', 'Sablon Kaos')->first();
        $banner = Service::query()->where('name', 'Printing Banner')->first();

        Portfolio::create([
            'title' => 'Kaos Komunitas Skull Rider',
            'image_url' => 'https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=800',
            'service_id' => $sablonKaos->id,
            'description' => 'Sablon screen print 2 warna untuk komunitas motor',
            'is_featured' => true,
            'sort_order' => 1,
        ]);

        Portfolio::create([
            'title' => 'Banner Event Musik',
            'image_url' => 'https://images.unsplash.com/photo-1558655146-d09347e92766?w=800',
            'service_id' => $banner->id,
            'description' => 'Banner flexi chinch 3x2 meter untuk event musik',
            'is_featured' => true,
            'sort_order' => 2,
        ]);

        Portfolio::create([
            'title' => 'Kaos Tim Football Club',
            'image_url' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800',
            'service_id' => $sablonKaos->id,
            'description' => 'Jersey bola custom dengan sablon DTG full print',
            'is_featured' => true,
            'sort_order' => 3,
        ]);

        Portfolio::create([
            'title' => 'Stiker Packaging',
            'image_url' => 'https://images.unsplash.com/photo-1611930020655-e7c0c8c4c9f9?w=800',
            'service_id' => Service::query()->where('name', 'Cetak Stiker')->first()->id,
            'description' => 'Stiker vinyl glossy untuk packaging produk',
            'is_featured' => false,
            'sort_order' => 4,
        ]);

        Portfolio::create([
            'title' => 'Seragam Bordir',
            'image_url' => 'https://images.unsplash.com/photo-1593030761757-71fae45fa0e7?w=800',
            'service_id' => Service::query()->where('name', 'Bordir')->first()->id,
            'description' => 'Seragam perusahaan dengan bordir logo',
            'is_featured' => false,
            'sort_order' => 5,
        ]);

        $this->call([
            ColorSeeder::class,
            SettingSeeder::class,
            TestimonialSeeder::class,
            SizeSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
        ]);
    }
}
