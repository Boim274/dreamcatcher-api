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

        $sizes = [
            ['name' => 'S', 'dimensions' => 'Lebar 40cm, Panjang 60cm', 'service_id' => $kaos?->id, 'sort_order' => 1],
            ['name' => 'M', 'dimensions' => 'Lebar 42cm, Panjang 62cm', 'service_id' => $kaos?->id, 'sort_order' => 2],
            ['name' => 'L', 'dimensions' => 'Lebar 44cm, Panjang 64cm', 'service_id' => $kaos?->id, 'sort_order' => 3],
            ['name' => 'XL', 'dimensions' => 'Lebar 46cm, Panjang 66cm', 'service_id' => $kaos?->id, 'sort_order' => 4],
            ['name' => 'XXL', 'dimensions' => 'Lebar 48cm, Panjang 68cm', 'service_id' => $kaos?->id, 'sort_order' => 5],
            ['name' => 'XXXL', 'dimensions' => 'Lebar 50cm, Panjang 70cm', 'service_id' => $kaos?->id, 'sort_order' => 6],
        ];

        foreach ($sizes as $size) {
            Size::updateOrCreate(
                ['name' => $size['name'], 'service_id' => $size['service_id']],
                $size
            );
        }
    }
}
