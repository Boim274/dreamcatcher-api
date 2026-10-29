<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ShirtModelSeeder extends Seeder
{
    public function run(): void
    {
        $kaosCat = Category::where('slug', 'kaos')->first();
        if (!$kaosCat) {
            $kaosCat = Category::create([
                'name' => 'Kaos',
                'slug' => 'kaos',
                'sort_order' => 1,
            ]);
        }

        // 1. Kaos Lengan Pendek
        $pShort = Product::where('category_id', $kaosCat->id)
            ->where(function ($q) {
                $q->where('name', 'like', '%Pendek%')->orWhere('id', 1);
            })->first();

        if (!$pShort) {
            $pShort = new Product();
            $pShort->category_id = $kaosCat->id;
        }

        $pShort->name = 'Kaos Lengan Pendek';
        $pShort->description = 'Kaos lengan pendek klasik standar distro. Terbuat dari katun combed 30s/24s premium yang adem, halus, nyaman, dan sangat ideal untuk segala teknik sablon presisi.';
        $pShort->base_price = 60000;
        $pShort->stock = 100;
        $pShort->is_active = true;
        $pShort->sort_order = 1;
        $pShort->save();

        $pShort->images()->delete();
        $pShort->images()->create([
            'image_url' => '/products/kaos-lengan-pendek.jpg',
            'sort_order' => 0,
        ]);

        // 2. Kaos Lengan Panjang
        $pLong = Product::where('category_id', $kaosCat->id)
            ->where('name', 'like', '%Panjang%')->first();

        if (!$pLong) {
            $pLong = new Product();
            $pLong->category_id = $kaosCat->id;
        }

        $pLong->name = 'Kaos Lengan Panjang';
        $pLong->description = 'Kaos lengan panjang yang nyaman digunakan untuk berbagai aktivitas, baik sehari-hari, bekerja, maupun kegiatan komunitas. Dibuat dengan bahan berkualitas yang lembut dan nyaman di kulit, serta jahitan rapi untuk memberikan hasil yang maksimal.';
        $pLong->base_price = 67000;
        $pLong->stock = 100;
        $pLong->is_active = true;
        $pLong->sort_order = 2;
        $pLong->save();

        $pLong->images()->delete();
        $pLong->images()->create([
            'image_url' => '/products/kaos-lengan-panjang.jpg',
            'sort_order' => 0,
        ]);

        // Sizes and colors for both products
        $sizesList = ['S', 'M', 'L', 'XL', 'XXL'];
        $colorsList = [
            ['name' => 'Hitam', 'hex_code' => '#000000'],
            ['name' => 'Putih', 'hex_code' => '#FFFFFF'],
            ['name' => 'Navy', 'hex_code' => '#172554'],
            ['name' => 'Maroon', 'hex_code' => '#7F1D1D'],
            ['name' => 'Hijau Army', 'hex_code' => '#16A34A'],
        ];

        foreach ([$pShort, $pLong] as $prod) {
            $prod->sizes()->delete();
            foreach ($sizesList as $idx => $sz) {
                $prod->sizes()->create(['name' => $sz, 'stock' => 20, 'sort_order' => $idx]);
            }

            $prod->colors()->delete();
            foreach ($colorsList as $idx => $col) {
                $prod->colors()->create(['name' => $col['name'], 'hex_code' => $col['hex_code'], 'sort_order' => $idx]);
            }
        }

        // Also update Service Sablon Kaos options_config to include shirt_models
        $service = Service::find(1);
        if ($service) {
            $optionsConfig = $service->options_config ?? [];
            if (is_string($optionsConfig)) {
                $optionsConfig = json_decode($optionsConfig, true) ?: [];
            }

            $optionsConfig['shirt_models'] = [
                [
                    'id' => 'short_sleeve',
                    'product_id' => $pShort->id,
                    'name' => 'Kaos Lengan Pendek',
                    'name_en' => 'Short Sleeve T-Shirt',
                    'description' => 'Model kaos klasik lengan pendek standar distro. Terbuat dari katun combed 30s/24s premium yang adem, halus, nyaman, dan fleksibel untuk segala jenis sablon.',
                    'image' => '/products/kaos-lengan-pendek.jpg',
                    'base_price' => (float) $pShort->base_price,
                    'surcharge' => 0,
                    'is_active' => true,
                ],
                [
                    'id' => 'long_sleeve',
                    'product_id' => $pLong->id,
                    'name' => 'Kaos Lengan Panjang',
                    'name_en' => 'Long Sleeve T-Shirt',
                    'description' => 'Kaos lengan panjang yang nyaman digunakan untuk berbagai aktivitas, baik sehari-hari, bekerja, maupun kegiatan komunitas. Dibuat dengan bahan berkualitas yang lembut dan nyaman di kulit, serta jahitan rapi untuk memberikan hasil yang maksimal.',
                    'image' => '/products/kaos-lengan-panjang.jpg',
                    'base_price' => (float) $pLong->base_price,
                    'surcharge' => 7000,
                    'is_active' => true,
                ],
            ];

            $service->options_config = $optionsConfig;
            $service->save();
        }
    }
}
