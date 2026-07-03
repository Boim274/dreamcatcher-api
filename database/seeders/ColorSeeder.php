<?php

namespace Database\Seeders;

use App\Models\Color;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
    public function run(): void
    {
        $colors = [
            ['name' => 'Putih', 'hex_code' => '#FFFFFF', 'sort_order' => 1],
            ['name' => 'Hitam', 'hex_code' => '#000000', 'sort_order' => 2],
            ['name' => 'Abu Muda', 'hex_code' => '#D3D3D3', 'sort_order' => 3],
            ['name' => 'Abu Tua', 'hex_code' => '#555555', 'sort_order' => 4],
            ['name' => 'Merah', 'hex_code' => '#DC2626', 'sort_order' => 5],
            ['name' => 'Biru Dongker', 'hex_code' => '#1E3A5F', 'sort_order' => 6],
            ['name' => 'Biru Muda', 'hex_code' => '#3B82F6', 'sort_order' => 7],
            ['name' => 'Hijau', 'hex_code' => '#16A34A', 'sort_order' => 8],
            ['name' => 'Kuning', 'hex_code' => '#EAB308', 'sort_order' => 9],
            ['name' => 'Orange', 'hex_code' => '#F97316', 'sort_order' => 10],
            ['name' => 'Navy', 'hex_code' => '#172554', 'sort_order' => 11],
            ['name' => 'Maroon', 'hex_code' => '#7F1D1D', 'sort_order' => 12],
            ['name' => 'Biru Toska', 'hex_code' => '#0D9488', 'sort_order' => 13],
            ['name' => 'Lavender', 'hex_code' => '#A78BFA', 'sort_order' => 14],
            ['name' => 'Pink', 'hex_code' => '#EC4899', 'sort_order' => 15],
            ['name' => 'Cream', 'hex_code' => '#FEF3C7', 'sort_order' => 16],
        ];

        foreach ($colors as $color) {
            Color::updateOrCreate(
                ['name' => $color['name']],
                $color
            );
        }
    }
}
