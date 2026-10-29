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
                'description' => 'Jasa sablon kaos custom berkualitas standar distro dengan teknik DTF High Definition dan Plastisol curing premium. Menggunakan bahan 100% Cotton Combed 24s/30s yang adem, halus, dan nyaman. Hasil cetak beresolusi tinggi, warna tajam, elastis, tidak mudah retak, dan tahan cuci berkali-kali. Melayani pemesanan satuan tanpa minimum order hingga partai besar untuk komunitas, event, dan brand clothing.',
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
                'description' => 'Layanan cetak banner, spanduk, dan backdrop outdoor maupun indoor dengan mesin digital printing beresolusi tinggi. Menggunakan pilihan bahan Flexi Korea, Flexi China, Albatros, dan Vinyl tebal dengan tinta anti pudar yang tahan cuaca panas dan hujan. Sangat cocok untuk kebutuhan promosi bisnis, pameran, event perusahaan, seminar, dan branding outlet.',
                'base_price' => 25000,
                'price_per_unit' => null,
                'minimum_order' => 1,
                'thumbnail' => null,
                'is_active' => true,
                'pricing_type' => 'flat',
                'pricing_config' => null,
                'options_config' => json_encode([
                    'sizes' => ['60x160cm', '80x200cm', '100x250cm'],
                    'materials' => ['Flexi China', 'Flexi Korea', 'Albatros', 'Vinyl', 'Fabric'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cetak Stiker',
                'description' => 'Cetak stiker custom label produk dan merchandise dengan hasil potong presisi (kiss-cut / die-cut). Menggunakan bahan Vinyl Glossy, Vinyl Matte (Doff), Transparan, dan Hologram berkualitas waterproof yang tahan air, minyak, dan goresan. Dilengkapi laminasi pelindung ekstra, ideal untuk kemasan UMKM, label botol/makanan, stiker kendaraan, dan promosi komunitas.',
                'base_price' => 5000,
                'price_per_unit' => null,
                'minimum_order' => 50,
                'thumbnail' => null,
                'is_active' => true,
                'pricing_type' => 'flat',
                'pricing_config' => null,
                'options_config' => json_encode([
                    'materials' => ['Vinyl Glossy', 'Vinyl Matte', 'Transparan', 'Hologram'],
                    'sizes' => ['5x5cm', '7x7cm', '10x10cm', 'Custom'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bordir',
                'description' => 'Layanan bordir komputer modern berpresisi tinggi dengan kerapatan benang rapat dan jahitan kokoh. Menggunakan benang bordir berkualitas tinggi yang berkilau, kuat, dan tidak mudah terurai saat dicuci. Sangat ideal untuk logo seragam kerja pabrik/kantor, kemeja polo, jaket, hoodie, rompi, topi, serta patch emblem komunitas dengan tampilan mewah dan berkelas.',
                'base_price' => 45000,
                'price_per_unit' => null,
                'minimum_order' => 6,
                'thumbnail' => null,
                'is_active' => true,
                'pricing_type' => 'flat',
                'pricing_config' => null,
                'options_config' => json_encode([
                    'positions' => ['Dada Kiri', 'Dada Kanan', 'Punggung', 'Lengan Kiri', 'Lengan Kanan', 'Topi Depan'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($services as $service) {
            DB::table('services')->updateOrInsert(
                ['name' => $service['name']],
                $service
            );
        }
    }
}
