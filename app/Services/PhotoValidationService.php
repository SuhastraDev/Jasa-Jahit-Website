<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

/**
 * Validasi foto pengukuran badan menggunakan Google Gemini Vision (GRATIS).
 * Free tier: 15 request/menit, 1 juta token/hari — cukup untuk produksi skala kecil.
 * Daftar API key: https://aistudio.google.com/app/apikey
 */
class PhotoValidationService
{
    protected string $apiKey;
    // Gemini 2.0 Flash — gratis, cepat, multimodal (vision)
    protected string $model = 'gemini-2.0-flash';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', '');
    }

    /**
     * Validasi foto sebelum dikirim ke CV service.
     * Return: ['valid' => bool, 'issues' => string[], 'suggestion' => string]
     */
    public function validate(UploadedFile $photo, string $refObject, string $orientation = 'front'): array
    {
        if (empty($this->apiKey)) {
            // API key tidak ada → skip validasi, lanjutkan ke CV
            return ['valid' => true, 'issues' => [], 'suggestion' => ''];
        }

        $refLabel = match($refObject) {
            'a4'     => 'kertas A4 (ukuran 21×29,7 cm) yang diletakkan di samping tubuh',
            'atm'    => 'kartu ATM atau KTP (ukuran 8,6×5,4 cm) yang diletakkan di samping tubuh',
            'aruco_a4' => 'marker ArUco ukuran A4 yang berdiri sendiri di samping tubuh',
            'checkerboard_a4' => 'marker checkerboard ukuran A4 yang berdiri sendiri di samping tubuh',
            'custom' => 'benda referensi berukuran kustom yang diletakkan di samping tubuh',
            default  => 'benda referensi yang diletakkan di samping tubuh',
        };

        $poseLabel = match ($orientation) {
            'side' => 'tampak samping penuh; user menghadap kiri atau kanan, bukan menghadap kamera',
            'back' => 'tampak belakang penuh; punggung menghadap kamera',
            default => 'tampak depan penuh; wajah dan badan menghadap kamera',
        };

        $prompt = <<<PROMPT
Kamu adalah sistem AI untuk memvalidasi foto pengukuran badan pada platform jasa jahit online.

Analisis foto ini. Semua syarat berikut HARUS terpenuhi agar foto dinyatakan VALID:

1. Ada tepat SATU orang yang berdiri tegak dengan posisi {$poseLabel}
2. Seluruh tubuh terlihat — dari ujung kepala hingga ujung kaki
3. Ada benda referensi berupa {$refLabel} yang terlihat jelas, tegak, tidak dipegang oleh orang yang diukur, dan tidak menutup siluet tubuh
4. Pencahayaan memadai — tidak terlalu gelap, tidak silau berlebihan
5. Foto tidak buram (tidak blur atau goyang)
6. Foto bukan foto random, foto produk, pemandangan, hewan, atau konten tidak relevan
7. Orang berdiri, bukan duduk, jongkok, atau terlihat setengah badan
8. Tangan user rileks sedikit menjauh dari badan dan tidak memegang benda referensi

Jawab HANYA dengan JSON tanpa markdown, tanpa teks lain:
{
  "valid": true atau false,
  "issues": ["masalah spesifik 1", "masalah spesifik 2"],
  "suggestion": "satu kalimat saran perbaikan jika tidak valid, atau string kosong jika valid"
}

Gunakan Bahasa Indonesia. Jika semua syarat terpenuhi, "valid" = true dan "issues" = [].
PROMPT;

        try {
            $imageData = base64_encode(file_get_contents($photo->getPathname()));
            $mimeType  = $photo->getMimeType() ?: 'image/jpeg';

            $response = Http::timeout(20)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data'      => $imageData,
                                    ],
                                ],
                                [
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature'     => 0.1,
                        'maxOutputTokens' => 300,
                    ],
                ]);

            if (!$response->successful()) {
                return ['valid' => true, 'issues' => [], 'suggestion' => ''];
            }

            $text   = $response->json('candidates.0.content.parts.0.text', '{}');
            // Bersihkan jika Gemini masih menambahkan markdown
            $text   = preg_replace('/```json|```/', '', $text);
            $result = json_decode(trim($text), true);

            if (!is_array($result)) {
                return ['valid' => true, 'issues' => [], 'suggestion' => ''];
            }

            return [
                'valid'      => (bool) ($result['valid'] ?? true),
                'issues'     => (array) ($result['issues'] ?? []),
                'suggestion' => (string) ($result['suggestion'] ?? ''),
            ];

        } catch (\Exception $e) {
            // Network error atau timeout → skip validasi
            return ['valid' => true, 'issues' => [], 'suggestion' => ''];
        }
    }
}
