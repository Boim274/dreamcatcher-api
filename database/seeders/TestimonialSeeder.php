<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'customer_name' => 'Budi Santoso',
                'rating' => 5,
                'review' => 'Hasil sablon sangat memuaskan! Warna cerah dan tidak mudah luntur. Pengiriman juga tepat waktu.',
                'sort_order' => 1,
            ],
            [
                'customer_name' => 'Rina Wati',
                'rating' => 5,
                'review' => 'Pelayanan ramah dan fast response. Kaos komunitas kami semua suka. Pasti order lagi!',
                'sort_order' => 2,
            ],
            [
                'customer_name' => 'Ahmad Fauzi',
                'rating' => 4,
                'review' => 'Kualitas DTF print-nya top! Detail gambar sangat tajam. Harga juga bersaing.',
                'sort_order' => 3,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::updateOrCreate(
                ['customer_name' => $testimonial['customer_name']],
                $testimonial
            );
        }
    }
}
