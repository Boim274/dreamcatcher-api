<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Color;
use App\Models\Order;
use App\Models\Portfolio;
use App\Models\Product;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Size;
use App\Models\Testimonial;

// Assembles a compact "DATA SISTEM" context block from the database so the AI
// answers with real products, prices, stock, and order status — never guesses.
class ChatContextService
{
    private const MAX_PRODUCTS = 30;
    private const MAX_PORTFOLIOS = 10;
    private const MAX_TESTIMONIALS = 10;
    private const MAX_CONTEXT_CHARS = 12000;

    public function buildContext(?string $latestUserMessage = null): string
    {
        // priority (lower = keep first when trimming) => section text
        $sections = [
            1 => $this->buildOrder($latestUserMessage),
            2 => $this->buildServices(),
            3 => $this->buildProducts(),
            4 => $this->buildSettings(),
            5 => $this->buildColors(),
            6 => $this->buildSizes(),
            7 => $this->buildCategories(),
            8 => $this->buildPortfolios(),
            9 => $this->buildTestimonials(),
        ];
        $sections = array_filter($sections, fn ($s) => $s !== '');

        if (empty($sections)) {
            return '';
        }

        ksort($sections);

        $header = "# DATA SISTEM DREAMCATCHER (sumber kebenaran utama)\n";
        $footer = "\n\nGunakan data ini untuk menjawab. Jika info tidak ada di sini, jawab: \"Maaf, informasi tersebut belum tersedia pada sistem kami.\"";
        $budget = self::MAX_CONTEXT_CHARS - strlen($header) - strlen($footer);

        $parts = [];
        $used = 0;
        foreach ($sections as $text) {
            $block = $text;
            if ($used > 0) {
                $block = "\n\n" . $block;
            }
            if ($used + strlen($block) > $budget) {
                // Keep the highest-priority sections within budget; drop the rest.
                break;
            }
            $parts[] = $text;
            $used += strlen($block);
        }

        return $header . implode("\n\n", $parts) . $footer;
    }

    private function buildServices(): string
    {
        $services = Service::active()->orderBy('created_at', 'desc')->get();
        if ($services->isEmpty()) {
            return '';
        }

        $lines = ['## Layanan Sablon & Harga'];
        foreach ($services as $s) {
            $config = $s->pricing_config ?? [];
            $priceInfo = '';
            if ($s->pricing_type === 'flat' || $config === []) {
                $priceInfo = 'Harga: Rp ' . number_format((float) $s->base_price, 0, ',', '.');
            } else {
                $parts = [];
                foreach ($config as $sablon => $levels) {
                    $p = [];
                    if (isset($levels['satuan'])) {
                        $p[] = 'satuan Rp ' . number_format((float) $levels['satuan'], 0, ',', '.');
                    }
                    if (isset($levels['lusin_1'])) {
                        $p[] = '12 pcs Rp ' . number_format((float) $levels['lusin_1'], 0, ',', '.');
                    }
                    if (isset($levels['lusin_2_6'])) {
                        $p[] = '24-72 pcs Rp ' . number_format((float) $levels['lusin_2_6'], 0, ',', '.');
                    }
                    if (!empty($p)) {
                        $parts[] = $sablon . ': ' . implode(', ', $p);
                    }
                }
                $priceInfo = 'Harga: ' . implode(' | ', $parts);
            }

            $line = '- ' . $s->name . ': ' . $priceInfo;
            if ((float) $s->price_per_unit > 0) {
                $line .= ' | per unit Rp ' . number_format((float) $s->price_per_unit, 0, ',', '.');
            }
            if ((int) $s->minimum_order > 0) {
                $line .= ' | min. order ' . $s->minimum_order . ' pcs';
            }
            if (!empty($s->description)) {
                $desc = trim(strip_tags($s->description));
                if (strlen($desc) > 160) {
                    $desc = mb_substr($desc, 0, 157) . '...';
                }
                $line .= ' | ' . $desc;
            }

            $options = $s->options_config ?? [];
            if (!empty($options['print_positions'])) {
                $positions = collect($options['print_positions'])->pluck('label')->filter()->implode(', ');
                if ($positions !== '') {
                    $line .= ' | posisi cetak: ' . $positions;
                }
            }
            if (!empty($options['print_sizes'])) {
                $pSizes = collect($options['print_sizes'])->pluck('label')->filter()->implode(', ');
                if ($pSizes !== '') {
                    $line .= ' | ukuran cetak: ' . $pSizes;
                }
            }

            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    private function buildProducts(): string
    {
        $products = Product::active()
            ->with(['category', 'sizes', 'colors'])
            ->ordered()
            ->take(self::MAX_PRODUCTS)
            ->get();

        if ($products->isEmpty()) {
            return '';
        }

        $lines = ['## Produk Kaos'];
        foreach ($products as $p) {
            $line = '- ' . $p->name . ': Rp ' . number_format((float) $p->base_price, 0, ',', '.');
            if ($p->category) {
                $line .= ' | kategori: ' . $p->category->name;
            }
            $line .= ' | stok: ' . (int) $p->stock;

            $sizes = $p->sizes->map(fn ($s) => $s->name . ' (stok ' . (int) $s->stock . ')')->implode(', ');
            if ($sizes !== '') {
                $line .= ' | ukuran: ' . $sizes;
            }

            $colors = $p->colors->pluck('name')->filter()->implode(', ');
            if ($colors !== '') {
                $line .= ' | warna: ' . $colors;
            }

            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    private function buildColors(): string
    {
        $colors = Color::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name')
            ->filter()
            ->implode(', ');
        return $colors === '' ? '' : "## Warna Kaos Tersedia\n" . $colors;
    }

    private function buildSizes(): string
    {
        $sizes = Size::orderBy('sort_order')->pluck('name')->unique()->implode(', ');
        return $sizes === '' ? '' : "## Ukuran Tersedia\n" . $sizes;
    }

    private function buildCategories(): string
    {
        $categories = Category::ordered()->pluck('name')->filter()->implode(', ');
        return $categories === '' ? '' : "## Kategori Produk\n" . $categories;
    }

    private function buildSettings(): string
    {
        $groups = ['general', 'contact', 'promo', 'payment'];
        $items = [];
        foreach ($groups as $group) {
            foreach (Setting::getGroup($group) as $key => $value) {
                if (is_string($value) && trim($value) !== '') {
                    $items[] = $key . ': ' . trim($value);
                }
            }
        }

        if (empty($items)) {
            return '';
        }

        return "## Informasi Toko\n" . implode("\n", $items);
    }

    private function buildPortfolios(): string
    {
        $portfolios = Portfolio::with('service')
            ->ordered()
            ->take(self::MAX_PORTFOLIOS)
            ->get();

        if ($portfolios->isEmpty()) {
            return '';
        }

        $lines = ['## Portofolio'];
        foreach ($portfolios as $pf) {
            $line = '- ' . ($pf->title ?: '(tanpa judul)');
            if ($pf->service) {
                $line .= ' | layanan: ' . $pf->service->name;
            }
            if (!empty($pf->description)) {
                $desc = trim(strip_tags($pf->description));
                if (strlen($desc) > 120) {
                    $desc = mb_substr($desc, 0, 117) . '...';
                }
                $line .= ' | ' . $desc;
            }
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    private function buildTestimonials(): string
    {
        $testimonials = Testimonial::active()
            ->ordered()
            ->take(self::MAX_TESTIMONIALS)
            ->get();

        if ($testimonials->isEmpty()) {
            return '';
        }

        $lines = ['## Testimoni Pelanggan'];
        foreach ($testimonials as $t) {
            $line = '- ' . ($t->customer_name ?: 'Pelanggan') . ' (' . (int) $t->rating . '/5): ';
            $review = trim(strip_tags($t->review));
            if (strlen($review) > 140) {
                $review = mb_substr($review, 0, 137) . '...';
            }
            $lines[] = $line . $review;
        }

        return implode("\n", $lines);
    }

    private function buildOrder(?string $message): string
    {
        if (!$message) {
            return '';
        }

        $code = $this->extractOrderCode($message);
        if ($code === null) {
            return '';
        }

        $order = Order::with(['items', 'statusHistory'])
            ->where('order_code', $code)
            ->first();

        if (!$order) {
            return "## Status Pesanan\nKode pesanan " . $code . ' tidak ditemukan di sistem.';
        }

        $lines = [
            '## Status Pesanan ' . $order->order_code,
            '- Status: ' . $order->status,
            '- Status pembayaran: ' . $order->payment_status,
            '- Total: Rp ' . number_format((float) $order->total_price, 0, ',', '.'),
            '- Metode pengiriman: ' . ($order->delivery_method ?: '-'),
        ];

        if ($order->items->isNotEmpty()) {
            $itemLines = $order->items->map(function ($item) {
                $name = $item->product_name ?? $item->variant_label ?? 'Item';
                $parts = [$name];
                if ($item->size) $parts[] = 'ukuran ' . $item->size;
                if ($item->quantity) $parts[] = 'x' . $item->quantity;
                if ($item->selected_color) $parts[] = 'warna ' . $item->selected_color;
                if ($item->sablon_type) $parts[] = 'sablon ' . $item->sablon_type;
                if ($item->print_area) $parts[] = 'area ' . $item->print_area;
                if ($item->print_size) $parts[] = 'ukuran cetak ' . $item->print_size;
                return '- ' . implode(', ', $parts);
            })->implode("\n");
            if ($itemLines !== '') {
                $lines[] = 'Items:';
                $lines[] = $itemLines;
            }
        }

        if ($order->statusHistory->isNotEmpty()) {
            $hist = $order->statusHistory->map(fn ($sh) => '- ' . ($sh->created_at ? $sh->created_at->format('d M H:i') : '') . ' ' . $sh->status)->implode("\n");
            if ($hist !== '') {
                $lines[] = 'Riwayat status:';
                $lines[] = $hist;
            }
        }

        return implode("\n", $lines);
    }

    private function extractOrderCode(string $message): ?string
    {
        if (preg_match('/DC\d{8}\d{4}/', $message, $m)) {
            return $m[0];
        }
        return null;
    }
}
