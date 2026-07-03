<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $kaos = Category::where('slug', 'kaos')->first();
        $merch = Category::where('slug', 'merch')->first();

        $products = [
            [
                'name' => 'Kaos Premium Dreamcatcher',
                'description' => 'Kaos premium quality dengan bahan cotton combed 30s. Nyaman dipakai sehari-hari dengan desain eksklusif Dreamcatcher.',
                'base_price' => 85000,
                'category_id' => $kaos?->id,
                'stock' => 50,
                'sizes' => [
                    ['name' => 'S', 'stock' => 10],
                    ['name' => 'M', 'stock' => 15],
                    ['name' => 'L', 'stock' => 15],
                    ['name' => 'XL', 'stock' => 10],
                ],
                'colors' => [
                    ['name' => 'Hitam', 'hex_code' => '#000000'],
                    ['name' => 'Putih', 'hex_code' => '#FFFFFF'],
                    ['name' => 'Navy', 'hex_code' => '#172554'],
                ],
                'images' => [
                    'https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=600',
                    'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=600',
                ],
            ],
            [
                'name' => 'Totebag Canvas Dreamcatcher',
                'description' => 'Totebag kanvas berkualitas dengan sablon logo Dreamcatcher. Cocok untuk belanja atau daily use.',
                'base_price' => 45000,
                'category_id' => $merch?->id,
                'stock' => 30,
                'sizes' => [],
                'colors' => [
                    ['name' => 'Cream', 'hex_code' => '#FEF3C7'],
                    ['name' => 'Hitam', 'hex_code' => '#000000'],
                ],
                'images' => [
                    'https://images.unsplash.com/photo-1544816155-12df9643f363?w=600',
                ],
            ],
            [
                'name' => 'Topi Baseball Dreamcatcher',
                'description' => 'Topi baseball dengan bordir logo Dreamcatcher. Adjustable strap, one size fits all.',
                'base_price' => 55000,
                'category_id' => $merch?->id,
                'stock' => 25,
                'sizes' => [
                    ['name' => 'One Size', 'stock' => 25],
                ],
                'colors' => [
                    ['name' => 'Hitam', 'hex_code' => '#000000'],
                    ['name' => 'Navy', 'hex_code' => '#172554'],
                ],
                'images' => [
                    'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=600',
                ],
            ],
            [
                'name' => 'Mug Custom Dreamcatcher',
                'description' => 'Mug keramik premium dengan sablon logo Dreamcatcher. Tahan panas, microwave safe.',
                'base_price' => 35000,
                'category_id' => $merch?->id,
                'stock' => 40,
                'sizes' => [],
                'colors' => [
                    ['name' => 'Putih', 'hex_code' => '#FFFFFF'],
                ],
                'images' => [
                    'https://images.unsplash.com/photo-1514228742587-6b1558fcca3d?w=600',
                ],
            ],
        ];

        foreach ($products as $data) {
            $images = $data['images'] ?? [];
            $sizes = $data['sizes'] ?? [];
            $colors = $data['colors'] ?? [];
            unset($data['images'], $data['sizes'], $data['colors']);

            $product = Product::create($data);

            foreach ($images as $i => $url) {
                $product->images()->create([
                    'image_url' => $url,
                    'sort_order' => $i,
                ]);
            }

            foreach ($sizes as $i => $size) {
                $product->sizes()->create([
                    'name' => $size['name'],
                    'stock' => $size['stock'],
                    'sort_order' => $i,
                ]);
            }

            foreach ($colors as $i => $color) {
                $product->colors()->create([
                    'name' => $color['name'],
                    'hex_code' => $color['hex_code'],
                    'sort_order' => $i,
                ]);
            }
        }
    }
}
