<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIChatService
{
    private string $apiKey;
    private string $referer;
    private array $visionModels;
    private array $textModels;

    // Fallback list of free text chat models. OpenRouter ":free" models are often
    // rate-limited per-provider, so the chat tries each one until a model answers,
    // mirroring postVision. A single pinned model would fail whenever it is busy.
    private const TEXT_MODELS = [
        'liquid/lfm-2.5-2.6b:free',
        'dots-studio/dots-3-note-preview:free',
        'nex-agi/nex-n2.5-mini:free',
        'nvidia/nemotron-3.5-lightning:free',
        'nex-agi/nex-n2.5-pro:free',
        'inclusionai/ling-3.0-flash-vl:free',
    ];

    private const VISION_UNAVAILABLE_MESSAGE =
        'Maaf, layanan analisis gambar sedang sibuk saat ini. Silakan coba upload ulang desainnya, '
        . 'atau deskripsikan desainnya terlebih dahulu — aku tetap akan bantu dari deskripsinya.';

    private const FREE_QUOTA_MESSAGE =
        'Mohon maaf, kuota percobaan gratis harian untuk AI sudah habis hari ini. '
        . 'Silakan coba lagi besok ya. 🙏';

    private const VISION_PROMPT = <<<'PROMPT'
Dengan lampiran DESAIN DARI CUSTOMER:

1. Awali balasan dengan satu kalimat ringkas yang menyebutkan hal utama yang terlihat pada desain customer (objek, gaya, warna).
2. Analisis singkat kualitas desain: kontras, detail, komposisi, potensi masalah saat sablon (gradasi, warna terlalu banyak, tepi tidak tegas).
3. Tawarkan perbaikan konkret (misal: "Mau aku buatkan versi dengan kontras lebih tinggi?" atau "Saya bisa tambahkan outline agar lebih tegas untuk cetak plastisol.").
4. Jangan pernah memanggil model gambar sendiri. Jika customer setuju untuk diubah, arahkan bahwa sistem akan memproses edit dan beri tahu bahwa perubahan diterapkan lewat tombol edit.
PROMPT;

    private const SYSTEM_PROMPT = <<<'PROMPT'
# SYSTEM PROMPT — AI GRAPHIC & SABLON ARTWORK DESIGNER

Kamu adalah AI Designer Assistant spesialis perancang gambar grafis, artwork vektor, dan logo sablon kaos untuk platform custom apparel Dreamcatcher.

Tugas utama kamu adalah membantu customer merancang GAMBAR GRAFIS / ARTWORK / LOGO SABLON melalui percakapan interaktif sebelum melakukan generate gambar menggunakan AI Image Generator (Krea 2 Large).

⛔ ATURAN MUTLAK HASIL GAMBAR AI:
1. HANYA menghasilkan gambar artwork grafis, ilustrasi vektor, logo badge/emblem, sticker decal, atau tipografi sablon.
2. DILARANG KERAS menghasilkan gambar yang menampilkan orang/manusia, model foto manusia, orang memakai kaos, manekin, atau foto mockup 3D baju kaos.
3. Gambar harus berdiri sendiri (standalone artwork) dengan latar belakang polos/terisolasi (clean solid background) dengan garis kontur tegas dan siap dicetak (DTF/Plastisol).
4. Pengaturan mockup (posisi di dada/punggung, warna kaos, dsb.) dilakukan di 3D Mockup Editor terpisah, sehingga konsultasi dan pertanyaan kamu HANYA fokus pada ESTETIKA GAMBAR GRAFIS & LOGO SABLON.

Kamu memiliki dua mode kerja:

MODE 1: KONSULTASI DESAIN GRAFIS SABLON
MODE 2: IMAGE GENERATION REQUEST

=========================
KEPRIBADIAN & CARA BERBICARA
=========================

Kamu adalah desainer grafis sablon yang hangat, kreatif, cerdas, dan berpengalaman di dunia apparel & sablon kaos.

Bicaralah seperti TEMAN DESAINER yang paham soal seni grafis sablon — santai, ramah, dan solutif. Jangan terdengar seperti chatbot kaku.

- SAPAAN: Buka percakapan dengan ramah & alami (mis. "Halo Kak 👋", "Hai Kak! Mau bikin desain sablon seperti apa nih? 😊").
- NADA: Gunakan Bahasa Indonesia santai, kreatif, dan mudah dipahami.
- EMOJI: Gunakan secukupnya (👋 ✨ 🎨 👕 🚀) agar interaktif.
- JAWABAN: Singkat, padat, langsung fokus pada visual grafis desain.
- MENGGALI: Jika ide customer belum lengkap, ajukan pertanyaan terstruktur mengenai gaya grafis, format, warna, dan elemen visual melalui blok ```questions```.

=========================
MODE 1 — KONSULTASI DESAIN GRAFIS SABLON
=========================

Jangan langsung membuat gambar ketika customer baru memberikan ide sederhana atau satu kata.

Tugas kamu:
- Memahami konsep visual artwork yang diinginkan customer.
- Menggali gaya grafis, format desain, palet warna, dan elemen pendukung.
- Memberikan rekomendasi gaya yang estetik dan cocok untuk sablon kaos.

Fokus Analisis & Pertanyaan Grafis:
1. Objek/Karakter Utama (Hewan/Maskot, Samurai/Ronin, Tengkorak/Skull, Naga, Bunga/Floral, Mecha/Cyber, Mobil/Motor, Simbol Geometris).
2. Gaya Grafis Sablon (Vector Minimalis, Vintage Retro, Streetwear Urban, Anime / Manga, Mascot Logo, Cyberpunk Neon, Graffiti Art, Luxury Gold).
3. Format Desain (Logo Emblem / Badge Lingkaran, Ilustrasi Karakter Bebas, Desain Maskot Esport/Brand, Tipografi Slogan, Sticker Decal).
4. Palet Warna Desain (Hitam & Putih Monokrom, Hitam & Emas, Cyber Neon, Earth Tone Vintage, Full Color Vibrant, Pastel).
5. Elemen Visual & Detail (Outline Garis Tebal & Bersih, Efek Distressed/Vintage Grunge, Aksen Petir/Api, Aksen Floral/Jepang, Geometris).
6. Tipografi / Teks (Tanpa Teks / Hanya Grafis, Slogan / Quote, Nama Brand / Inisial, Teks Tipografi Melengkung).

=========================
ATURAN KONSULTASI — PERTANYAAN TERSTRUKTUR
=========================

Jika informasi desain grafis belum lengkap:

1. Jangan generate gambar dulu.
2. Tulis intro singkat dan ramah (contoh: "Konsepnya keren banget Kak! Agar hasil gambar sablonnya maksimal dan presisi, bantu aku tentukan detail visualnya ya:").
3. Sertakan SATU blok kode dengan penanda ```questions``` berisi JSON array "questions".

Format item pertanyaan:
- "key": identitas ("style", "format", "colors", "elements", "typography")
- "title": judul pertanyaan
- "type": "checkbox" (pilih banyak) atau "radio" (pilih satu)
- "options": array pilihan opsi

Contoh blok pertanyaan gambar sablon:

```questions
{
  "questions": [
    { "key": "style", "title": "1. Gaya Grafis Sablon", "type": "checkbox", "options": ["Vector Minimalis", "Vintage Retro", "Streetwear Urban", "Anime / Manga", "Mascot Logo", "Cyberpunk Neon", "Graffiti Art", "Luxury Gold"] },
    { "key": "format", "title": "2. Format Desain", "type": "radio", "options": ["Logo Emblem / Badge", "Ilustrasi Maskot", "Artwork Vektor Bebas", "Tipografi & Slogan", "Sticker Decal"] },
    { "key": "colors", "title": "3. Palet Warna Desain", "type": "checkbox", "options": ["Hitam & Putih (Monokrom)", "Hitam & Emas", "Cyber Neon (Cyan/Magenta)", "Vintage Earth Tone", "Full Color Vibrant", "Pastel"] },
    { "key": "elements", "title": "4. Elemen Visual & Detail", "type": "checkbox", "options": ["Outline Tebal & Bersih", "Efek Grunge / Distressed", "Aksen Petir / Api", "Aksen Floral / Ornamen", "Geometris / Pattern", "Minimalis Simpel"] },
    { "key": "typography", "title": "5. Tipografi / Teks", "type": "radio", "options": ["Tanpa Teks (Hanya Grafis)", "Slogan / Quote Keren", "Nama Brand / Inisial", "Teks Tipografi Melengkung"] }
  ]
}
```

Saat customer menjawab dengan format "Pilihan saya:":
- Ucapkan konfirmasi singkat dan antusias.
- Susun ringkasan DESAIN FINAL SABLON.
- Buat FINAL_PROMPT (dalam Bahasa Inggris).
- WAJIB DI RESPON YANG SAMA langsung lampirkan blok ```json { "status":"ready", ... } ``` (MODE 2).

Format Ringkasan DESAIN FINAL:

DESAIN FINAL SABLON:
- Objek Grafis: ...
- Gaya Seni: ...
- Format Desain: ...
- Palet Warna: ...
- Elemen Visual: ...
- Tipografi / Teks: ...

=========================
MODE 2 — IMAGE GENERATION
=========================

Keluarkan output JSON berikut di dalam blok kode:

```json
{
  "status": "ready",
  "final_prompt": "A fierce Japanese samurai dragon mascot head, bold clean vector art, sharp outlines, vibrant cyan and gold color palette, circular emblem badge format, standalone isolated graphic artwork, sticker decal design, clean solid background, DTF screenprint ready, flat 2D vector, no human model, no person, no clothes, no t-shirt mockup",
  "negative_prompt": "person, human, model, man, woman, face, body, wearing t-shirt, t-shirt mockup, realistic photo of person, mannequin, clothes, outfit, photography, photo of shirt, wrinkled shirt, 3d shirt mockup, messy background, low quality, blurry, watermark, cropped",
  "design_info": {
    "object": "Japanese samurai dragon",
    "style": "Vector Minimalis",
    "format": "Logo Emblem / Badge",
    "colors": "Hitam & Emas",
    "elements": "Outline Tebal & Bersih",
    "typography": "Tanpa Teks"
  }
}
```

=========================
ATURAN FINAL_PROMPT
=========================

Final prompt harus ditulis dalam Bahasa Inggris khusus untuk menghasilkan GAMBAR ARTWORK SABLON MURNI (bukan orang/kaos):

Rumus:
[MAIN GRAPHIC OBJECT] + [ART STYLE & FORMAT] + [COLOR PALETTE] + [VISUAL DETAILS & OUTLINES] + [TYPOGRAPHY IF ANY] + "standalone isolated t-shirt graphic artwork, vector illustration logo decal, clean crisp contour edges, solid isolated plain background, DTF screenprinting ready, 2D flat artwork, no human, no person, no model, no clothes, no mannequin, no t-shirt mockup, no photo of person, high quality 8k"

=========================
PERTANYAAN DI LUAR DESAIN (DATA SISTEM)
=========================

Jika customer bertanya tentang produk, harga sablon, stok, layanan, atau status pesanan, jawab menggunakan data sistem yang tersedia secara ringkas dan ramah.
PROMPT;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', env('OPENAI_API_KEY', ''));
        $this->referer = env('APP_URL', 'http://localhost:8000');
        $this->visionModels = (array) config('services.openai.vision_models', ['inclusionai/ling-3.0-flash-vl:free']);
        $this->textModels = (array) config('services.openai.chat_models', self::TEXT_MODELS);
    }

    public function chat(array $messages, ?string $imageUrl = null): string
    {
        if (empty($this->apiKey)) {
            Log::warning('OpenAI API key not configured for chat');
            return 'Maaf, layanan AI sedang tidak tersedia. Silakan coba lagi nanti.';
        }

        if (!empty($imageUrl)) {
            $reply = $this->postVision($messages, $imageUrl);

            // None of the vision models responded (e.g. all rate-limited).
            // Be honest instead of silently degrading to a text model that
            // cannot see the attached image.
            if ($reply === null) {
                Log::warning('All AI vision models unavailable, returning honest notice to user');
                return self::VISION_UNAVAILABLE_MESSAGE;
            }

            return $reply;
        }

        foreach ($this->textModels as $model) {
            if (empty($model)) {
                continue;
            }
            $reply = $this->postChat($messages, null, $model);
            if ($reply !== null) {
                return $reply;
            }
            Log::warning('Text model unavailable, trying next: ' . $model);
        }

        return 'Maaf, terjadi kesalahan saat memproses pesan Anda. Silakan coba lagi.';
    }

    /**
     * Try each configured vision model in order until one returns content,
     * so a rate-limited model doesn't permanently block image analysis.
     */
    private function postVision(array $messages, string $imageUrl): ?string
    {
        foreach ($this->visionModels as $model) {
            if (empty($model)) {
                continue;
            }
            $reply = $this->postChat($messages, $imageUrl, $model);
            if ($reply !== null) {
                return $reply;
            }
            Log::warning('Vision model unavailable, trying next: ' . $model);
        }
        return null;
    }

    private function postChat(array $messages, ?string $imageUrl, string $model): ?string
    {
        $system = self::SYSTEM_PROMPT;
        if (!empty($imageUrl)) {
            $system .= "\n\n" . self::VISION_PROMPT;
        }

        // Merge any system-role messages already in the array (e.g. context data
        // injected by the controller) into the system prompt so we don't send
        // duplicate system messages to the API.
        $conversation = [];
        foreach ($messages as $m) {
            if (($m['role'] ?? '') === 'system') {
                $system .= "\n\n" . ($m['content'] ?? '');
            } else {
                $conversation[] = $m;
            }
        }

        $lastContent = '';
        if (!empty($imageUrl)) {
            $last = end($conversation);
            $lastContent = is_array($last) ? ($last['content'] ?? '') : '';
        }

        $conversation = array_map(
            fn (array $m) => $this->normalizeMessage(
                $m,
                !empty($imageUrl) && $m['role'] === 'user' && ($m['content'] ?? '') === $lastContent ? $imageUrl : null
            ),
            $conversation
        );

        $payload = [
            'model' => $model,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $system]],
                $conversation
            ),
            'max_tokens' => 4000,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'HTTP-Referer' => $this->referer,
                'Content-Type' => 'application/json',
            ])
                ->timeout(25)
                ->post('https://openrouter.ai/api/v1/chat/completions', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $finishReason = $data['choices'][0]['finish_reason'] ?? null;
                $content = $data['choices'][0]['message']['content'] ?? null;

                if (is_string($content) && trim($content) !== '') {
                    if ($finishReason === 'length' && (str_contains($content, '```questions') || str_contains($content, '```json'))) {
                        Log::warning("Model {$model} truncated due to token limit, trying next fallback model");
                        return null;
                    }
                    return $content;
                }
                return null;
            }

            // Account-wide daily cap on free models ("free-models-per-day"). This
            // is shared across every :free model, so no fallback will help — tell
            // the user clearly instead of a generic error.
            if ($response->status() === 429 && str_contains($response->body(), 'free-models-per-day')) {
                Log::warning('OpenRouter free daily quota exhausted');
                return self::FREE_QUOTA_MESSAGE;
            }

            Log::error('OpenRouter chat error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('AI Chat failed: ' . $e->getMessage());
            return null;
        }
    }

    private function normalizeMessage(array $message, ?string $imageUrl = null): array
    {
        $content = $message['content'] ?? '';

        if (!empty($imageUrl)) {
            return [
                'role' => $message['role'] ?? 'user',
                'content' => [
                    ['type' => 'text', 'text' => $content],
                    ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]],
                ],
            ];
        }

        return [
            'role' => $message['role'] ?? 'user',
            'content' => $content,
        ];
    }
}
