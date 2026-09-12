<?php

namespace App\Services;

class ConversationTitleService
{
    private const STOPWORDS = [
        'buat', 'bikin', 'desain', 'gambar', 'kaos', 'kemeja', 'baju', 't-shirt', 'tshirt', 'shirt',
        'dengan', 'untuk', 'yang', 'dari', 'dan', 'atau', 'pada', 'di', 'ke', 'ini', 'itu',
        'gaya', 'style', 'menggunakan', 'pakai', 'mohon', 'tolong', 'saya', 'ingin', 'mau',
        'a', 'an', 'the', 'of', 'for', 'and', 'with', 'using', 'make', 'create', 'design',
        'please', 'want', 'would', 'like', 'to', 'in', 'on', 'is', 'be',
    ];

    public function generate(string $prompt): string
    {
        $cleaned = preg_replace('/[^\p{L}\p{N}\s-]/u', ' ', $prompt);
        $cleaned = preg_replace('/\s+/u', ' ', trim($cleaned ?? ''));

        $words = preg_split('/[\s-]+/u', $cleaned ?? '') ?: [];
        $keywords = [];

        foreach ($words as $word) {
            $lower = mb_strtolower($word);
            if (mb_strlen($word) < 3) {
                continue;
            }
            if (in_array($lower, self::STOPWORDS, true)) {
                continue;
            }
            $keywords[] = $word;
            if (count($keywords) >= 4) {
                break;
            }
        }

        if (count($keywords) === 0) {
            return 'Desain Baru';
        }

        return implode(' ', array_map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)) . mb_substr($w, 1), $keywords));
    }
}
