<?php

namespace Database\Seeders;

use App\Models\Size;
use App\Models\Service;
use Illuminate\Database\Seeder;

class SizeSeeder extends Seeder
{
    public function run(): void
    {
        $kaos = Service::where('name', 'Sablon Kaos')->first();

        $kaosId = $kaos?->id;

        $sizes = [
            // Dewasa
            ['name' => 'S', 'dimensions' => 'Lebar 40cm, Panjang 60cm', 'service_id' => $kaosId, 'sort_order' => 1],
            ['name' => 'M', 'dimensions' => 'Lebar 42cm, Panjang 62cm', 'service_id' => $kaosId, 'sort_order' => 2],
            ['name' => 'L', 'dimensions' => 'Lebar 44cm, Panjang 64cm', 'service_id' => $kaosId, 'sort_order' => 3],
            ['name' => 'XL', 'dimensions' => 'Lebar 46cm, Panjang 66cm', 'service_id' => $kaosId, 'sort_order' => 4],
            ['name' => 'XXL', 'dimensions' => 'Lebar 48cm, Panjang 68cm', 'service_id' => $kaosId, 'sort_order' => 5],
            ['name' => 'XXXL', 'dimensions' => 'Lebar 50cm, Panjang 70cm', 'service_id' => $kaosId, 'sort_order' => 6],

            // Anak-anak
            ['name' => 'Kids XS', 'dimensions' => 'Usia 1-2 Thn (Lebar 31cm, Panjang 40cm)', 'service_id' => $kaosId, 'sort_order' => 7],
            ['name' => 'Kids S', 'dimensions' => 'Usia 3-4 Thn (Lebar 34cm, Panjang 44cm)', 'service_id' => $kaosId, 'sort_order' => 8],
            ['name' => 'Kids M', 'dimensions' => 'Usia 5-6 Thn (Lebar 37cm, Panjang 48cm)', 'service_id' => $kaosId, 'sort_order' => 9],
            ['name' => 'Kids L', 'dimensions' => 'Usia 7-8 Thn (Lebar 40cm, Panjang 53cm)', 'service_id' => $kaosId, 'sort_order' => 10],
            ['name' => 'Kids XL', 'dimensions' => 'Usia 9-10 Thn (Lebar 43cm, Panjang 57cm)', 'service_id' => $kaosId, 'sort_order' => 11],
        ];

        foreach ($sizes as $size) {
            Size::updateOrCreate(
                ['name' => $size['name'], 'service_id' => $size['service_id']],
                $size
            );
        }
    }
}
