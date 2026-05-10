<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Dreamcatcher',
            'email' => 'admin@dreamcatcher.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'phone' => '081234567890',
        ]);

        Service::create([
            'name' => 'Sablon Kaos',
            'description' => 'Sablon kaos berkualitas tinggi dengan teknik DTG dan screen print. Cocok untuk desain custom dengan warna full color atau limited edition.',
            'base_price' => 50000,
            'price_per_unit' => 35000,
            'minimum_order' => 12,
            'thumbnail' => '/images/services/sablon-kaos.jpg',
            'is_active' => true,
        ]);

        Service::create([
            'name' => 'Printing Banner',
            'description' => 'Cetak banner dengan kualitas tinggi. Tersedia berbagai ukuran dan bahan seperti flexi chinch, vinyl, dan fabric.',
            'base_price' => 75000,
            'price_per_unit' => 25000,
            'minimum_order' => 1,
            'thumbnail' => '/images/services/banner.jpg',
            'is_active' => true,
        ]);

        Service::create([
            'name' => 'Cetak Stiker',
            'description' => 'Cetak stiker custom untuk berbagai kebutuhan. Tersedia pilihan bahan vinyl glossy, matte, dan transparan.',
            'base_price' => 25000,
            'price_per_unit' => 5000,
            'minimum_order' => 50,
            'thumbnail' => '/images/services/stiker.jpg',
            'is_active' => true,
        ]);

        Service::create([
            'name' => 'Bordir',
            'description' => 'Bordir komputer dengan hasil rapi dan profesional. Cocok untuk seragam perusahaan, komunitas, atau hadiah.',
            'base_price' => 100000,
            'price_per_unit' => 45000,
            'minimum_order' => 6,
            'thumbnail' => '/images/services/bordir.jpg',
            'is_active' => true,
        ]);

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
    }
}