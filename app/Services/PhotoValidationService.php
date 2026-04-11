<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class PhotoValidationService
{
    protected string $apiKey;
    protected string $model = 'claude-haiku-4-5-20251001'; // cepat & murah untuk validasi

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key', '');
    }

    /**
     * Validasi foto sebelum dikirim ke CV service.
     * Kembalikan: ['valid' => bool, 'issues' => string[], 'detail' => string]
     */
    public function validate(UploadedFile $photo, string $refObject): array
    {
        if (empty($this->apiKey)) {
            // Jika API key tidak ada, skip validasi (lanjut ke CV)
            return ['valid' => true, 'issues' => [], 'detail' => ''];
        }

        $refLabel = match($refObject) {
            'a4'     => 'kertas A4 (ukuran 21×29,7 cm)',
            'atm'    => 'kartu ATM/KTP (ukuran 8,6×5,4 cm)',
            'custom' => 'benda referensi ukuran kustom',
            default  => 'benda referensi',
        };

        $prompt = <<<PROMPT
Kamu adalah sistem validasi foto untuk pengukuran badan otomatis pada platform jasa jahit.

Analisis foto ini dan tentukan apakah foto VALID untuk dijadikan input pengukuran badan.

Syarat foto yang VALID (semua harus terpenuhi):
1. Ada satu orang yang berdiri tegak menghadap kamera (tampak depan penuh)
2. Seluruh tubuh terlihat dari ujung kepala hingga ujung kaki
3. Ada benda referensi berupa {$refLabel} yang terlihat jelas di sebelah atau depan tubuh orang tersebut
4. Pencahayaan cukup — tidak terlalu gelap atau silau
5. Foto tidak buram (blur)
6. Bukan foto random, produk, pemandangan, atau konten tidak relevan
7. Orang berdiri di area terbuka (bukan duduk, jongkok, atau setengah badan)

Respons HARUS dalam format JSON berikut (tanpa markdown, tanpa teks lain):
{
  "valid": true/false,
  "issues": ["masalah 1 jika ada", "masalah 2 jika ada"],
  "passed": ["syarat yang sudah terpenuhi"],
  "suggestion": "saran singkat untuk perbaikan jika tidak valid, atau kosong jika valid"
}

Gunakan Bahasa Indonesia. Jika valid, "issues" harus array kosong [].
PROMPT;

        try {
            $imageData   = base64_encode(file_get_contents($photo->getPathname()));
            $mimeType    = $photo->getMimeType();

            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model'      => $this->model,
                'max_tokens' => 512,
                'messages'   => [
                    [
                        'role'    => 'user',
                        'content' => [
                            [
                                'type'   => 'image',
                                'source' => [
                                    'type'       => 'base64',
                                    'media_type' => $mimeType,
                                    'data'       => $imageData,
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
            ]);

            if (!$response->successful()) {
                // Jika API gagal, biarkan lanjut (jangan blokir user)
                return ['valid' => true, 'issues' => [], 'detail' => ''];
            }

            $content = $response->json('content.0.text', '{}');
            $result  = json_decode($content, true);

            if (!is_array($result)) {
                return ['valid' => true, 'issues' => [], 'detail' => ''];
            }

            return [
                'valid'      => (bool) ($result['valid'] ?? true),
                'issues'     => (array) ($result['issues'] ?? []),
                'passed'     => (array) ($result['passed'] ?? []),
                'suggestion' => (string) ($result['suggestion'] ?? ''),
            ];

        } catch (\Exception $e) {
            // Jika network error, skip validasi
            return ['valid' => true, 'issues' => [], 'detail' => ''];
        }
    }
}
