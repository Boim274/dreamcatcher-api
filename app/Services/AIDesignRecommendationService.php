<?php

namespace App\Services;

class AIDesignRecommendationService
{
    public function catalog(): array
    {
        return [
            'colors' => [
                ['id' => 'light', 'label' => 'Terang', 'desc' => 'Putih, krem, pastel — kontras kuat di kaos gelap', 'swatch' => '#f5f5f5'],
                ['id' => 'dark', 'label' => 'Gelap', 'desc' => 'Hitam, navy, charcoal — elegan & cocok kaos terang', 'swatch' => '#111111'],
                ['id' => 'vibrant', 'label' => 'Vibrant', 'desc' => 'Merah, oranye, kuning — mencolok, streetwear', 'swatch' => '#ff3b30'],
                ['id' => 'neon', 'label' => 'Neon', 'desc' => 'Hijau neon, pink — kontras tinggi, efek menyala', 'swatch' => '#00e676'],
                ['id' => 'earth', 'label' => 'Earth Tone', 'desc' => 'Krem, coklat, olive — natural & vintage', 'swatch' => '#8d6e63'],
                ['id' => 'pastel', 'label' => 'Pastel', 'desc' => 'Lembut, cocok desain kartun & feminin', 'swatch' => '#ffb6c1'],
            ],
            'styles' => [
                ['id' => 'cartoon', 'label' => 'Kartun', 'desc' => 'Lucid & playful, cocok plastisol 2 warna'],
                ['id' => 'mascot', 'label' => 'Mascot', 'desc' => 'Karakter maskot, populer untuk komunitas'],
                ['id' => 'minimalist', 'label' => 'Minimalis', 'desc' => 'Sedikit elemen, banyak ruang kosong'],
                ['id' => 'vintage', 'label' => 'Vintage', 'desc' => 'Gaya retro, tekstur faded, earthy color'],
                ['id' => 'streetwear', 'label' => 'Streetwear', 'desc' => 'Bold graphic, tipografi besar, oversize look'],
                ['id' => 'graffiti', 'label' => 'Graffiti', 'desc' => 'Ekspresif, freehand, warna mencolok'],
                ['id' => 'realistic', 'label' => 'Realistik', 'desc' => 'Detail foto, hanya cocok DTF'],
                ['id' => 'anime', 'label' => 'Anime', 'desc' => 'Gaya Jepang, warna cerah, hanya DTF'],
                ['id' => 'vector', 'label' => 'Vector', 'desc' => 'Flat solid, warna terbatas, ideal plastisol'],
                ['id' => 'luxury', 'label' => 'Luxury', 'desc' => 'Elegan, monokrom, tipografi serif'],
            ],
            'typography' => [
                ['id' => 'bold', 'label' => 'Bold & Tebal', 'desc' => 'Impact, sans-serif tebal — streetwear'],
                ['id' => 'script', 'label' => 'Script / Tulisan Tangan', 'desc' => 'Elegant cursive, wedding & feminine'],
                ['id' => 'retro', 'label' => 'Retro Serif', 'desc' => 'Gaya vintage, klasik'],
                ['id' => 'minimal', 'label' => 'Minimal Sans', 'desc' => 'Bersih & modern'],
                ['id' => 'handmade', 'label' => 'Handmade / Grunge', 'desc' => 'Terkesan hand-drawn, edgy'],
                ['id' => 'gothic', 'label' => 'Gothic', 'desc' => 'Metal & hardcore, karakter dekoratif'],
            ],
            'graphics' => [
                ['id' => 'animal', 'label' => 'Hewan', 'desc' => 'Singa, harimau, beruang, elang'],
                ['id' => 'logo', 'label' => 'Logo / Monogram', 'desc' => 'Identitas brand'],
                ['id' => 'illustration', 'label' => 'Ilustrasi', 'desc' => 'Scene atau karakter kompleks'],
                ['id' => 'symbol', 'label' => 'Simbol & Ikon', 'desc' => 'Skull, flame, geometry, religius'],
                ['id' => 'human', 'label' => 'Manusia / Figur', 'desc' => 'Potret atau action figure'],
                ['id' => 'typography_only', 'label' => 'Tipografi Saja', 'desc' => 'Tanpa gambar, hanya teks'],
                ['id' => 'abstract', 'label' => 'Abstrak / Geometris', 'desc' => 'Pattern dan bentuk modern'],
                ['id' => 'texture', 'label' => 'Tekstur / Camo', 'desc' => 'Pattern berulang'],
            ],
            'layout' => [
                ['id' => 'center_chest', 'label' => 'Tengah Dada', 'desc' => 'Paling umum, area cetak 9x9 cm'],
                ['id' => 'center_full', 'label' => 'Tengah Besar', 'desc' => 'Front big print, A4/A3'],
                ['id' => 'left_chest', 'label' => 'Dada Kiri', 'desc' => 'Kecil, elegan, 9x9 cm'],
                ['id' => 'back', 'label' => 'Belakang', 'desc' => 'Punggung, sering untuk desain utama'],
                ['id' => 'sleeve', 'label' => 'Lengan', 'desc' => 'Kanan/kiri, aksen kecil'],
                ['id' => 'full_bleed', 'label' => 'Full Bleed', 'desc' => 'Menutupi sebagian besar badan kaos'],
            ],
            'print_methods' => [
                ['id' => 'plastisol', 'label' => 'Plastisol', 'desc' => 'Maksimal 2 warna solid, kontras tinggi, tanpa gradasi. Paling hemat.', 'rules' => ['max_colors' => 2, 'gradient' => false, 'vector' => true]],
                ['id' => 'dtf', 'label' => 'DTF', 'desc' => 'Full color, gradasi, detail tinggi, foto & realistik. Bebas warna.', 'rules' => ['max_colors' => null, 'gradient' => true, 'vector' => false]],
            ],
            'audiences' => [
                ['id' => 'anak', 'label' => 'Anak-anak', 'desc' => 'Warna cerah, karakter lucu, aman'],
                ['id' => 'remaja', 'label' => 'Remaja', 'desc' => 'Streetwear, bold, mengikuti tren'],
                ['id' => 'dewasa', 'label' => 'Dewasa', 'desc' => 'Elegan, minimalis, maskulin/feminin'],
                ['id' => 'komunitas', 'label' => 'Komunitas', 'desc' => 'Mascot, identitas kelompok'],
                ['id' => 'corporate', 'label' => 'Korporat', 'desc' => 'Logo, seragam, profesional'],
                ['id' => 'event', 'label' => 'Event / Merch', 'desc' => 'Momentum, tanggal, konser'],
            ],
            'themes' => [
                ['id' => 'makanan', 'label' => 'Makanan & Minuman'],
                ['id' => 'olahraga', 'label' => 'Olahraga & Fitnes'],
                ['id' => 'musik', 'label' => 'Musik & Band'],
                ['id' => 'alam', 'label' => 'Alam & Outdoor'],
                ['id' => 'teknologi', 'label' => 'Teknologi & Gaming'],
                ['id' => 'budaya', 'label' => 'Budaya & Lokal'],
                ['id' => 'humor', 'label' => 'Humor & Quote'],
                ['id' => 'religius', 'label' => 'Religius & Spiritual'],
            ],
        ];
    }

    public function smartSuggestions(?string $shirtColor = null): array
    {
        $suggestions = [
            'Gunakan maksimal 2 warna untuk Plastisol — pilih warna solid dengan kontras tinggi agar hasil cetak tajam.',
            'DTF mendukung full color & gradasi: cocok untuk desain realistik atau foto.',
            'Desain gelap cocok di atas kaos terang, desain terang cocok di atas kaos gelap — perhatikan kontras.',
            'Desain kompleks & detail tinggi sebaiknya dicetak DTF, bukan Plastisol.',
            'Area kecil seperti lengan cocok untuk desain sederhana & ikon, bukan teks panjang.',
            'Desain besar di punggung paling pas untuk ilustrasi lengkap atau artwork utama.',
            'Hindari teks terlalu kecil — saat dicetak di kaos, detail bisa hilang.',
            'Gradasi warna membutuhkan DTF. Plastisol tidak mendukung gradasi halus.',
            'Untuk logo komunitas, gunakan desain mascot yang mudah dikenali dari jauh.',
            'Pastikan gambar memiliki background transparan agar tidak muncul kotak putih saat ditempel.',
        ];

        if ($shirtColor) {
            $light = ['#ffffff', '#f5f5f5', '#eeeeee', '#fafafa', '#fffbeb'];
            $isLight = in_array(strtolower($shirtColor), $light, true);
            if ($isLight) {
                $suggestions[] = 'Kaos terang terpilih — desain gelap atau vibrant akan terlihat paling kontras.';
            } else {
                $suggestions[] = 'Kaos gelap terpilih — desain terang (putih, neon, pastel) lebih mencolok dan terlihat jelas.';
            }
        }

        return $suggestions;
    }
}
