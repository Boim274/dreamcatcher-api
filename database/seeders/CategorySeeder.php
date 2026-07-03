<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Kaos', 'sort_order' => 1],
            ['name' => 'Merch', 'sort_order' => 2],
            ['name' => 'Aksesoris', 'sort_order' => 3],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => Str::slug($cat['name'])],
                $cat
            );
        }
    }
}
