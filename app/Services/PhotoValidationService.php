<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

/**
 * Validasi foto pengukuran badan menggunakan Groq Vision.
 */
class PhotoValidationService
{
    protected string $apiKey;
    protected string $model;
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.groq.key', '');
        $this->model = config('services.groq.model', 'qwen/qwen3.6-27b');
        $this->apiUrl = config('services.groq.url', 'https://api.groq.com/openai/v1/chat/completions');
    }

    /**
     * Validasi foto sebelum dikirim ke CV service.
     * Return: ['valid' => bool, 'issues' => string[], 'suggestion' => string]
     */
    public function validate(UploadedFile $photo, string $refObject, string $orientation = 'front', string $referenceMode = 'fixed'): array
    {
        if (empty($this->apiKey)) {
            // API key tidak ada → skip validasi, lanjutkan ke CV
            return ['valid' => true, 'issues' => [], 'suggestion' => ''];
        }

        $refLabel = match($refObject) {
            'a4'     => 'kertas A4 (ukuran tetap 21,0×29,7 cm) yang diletakkan di samping tubuh',
            'ktp', 'atm' => 'KTP (ukuran tetap 8,56×5,398 cm) yang diletakkan di samping tubuh',
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

        $referenceRule = $referenceMode === 'handheld'
            ? 'Benda referensi boleh dipegang hanya jika berupa kertas A4. A4 harus berada di samping luar tubuh, sejajar tubuh, tidak maju ke arah kamera, tidak miring, dan tidak menutup dada, pinggang, pinggul, paha, atau kaki.'
            : 'Benda referensi harus berdiri sendiri atau ditempel pada dinding/papan/tripod. Benda referensi tidak boleh dipegang oleh orang yang diukur.';

        $handheldSideWarning = $referenceMode === 'handheld' && $orientation === 'side'
            ? 'Untuk foto samping, pastikan tangan dan A4 tidak menutup siluet dada, perut, pinggul, paha, atau kaki.'
            : '';

        $prompt = <<<PROMPT
Kamu adalah sistem AI untuk memvalidasi foto pengukuran badan pada platform jasa jahit online.

Analisis foto ini. Semua syarat berikut HARUS terpenuhi agar foto dinyatakan VALID:

1. Ada tepat SATU orang yang berdiri tegak dengan posisi {$poseLabel}
2. Seluruh tubuh terlihat — dari ujung kepala hingga ujung kaki
3. Ada benda referensi berupa {$refLabel} yang terlihat jelas, tegak, berada di samping tubuh, dan tidak menutup siluet tubuh
4. Pencahayaan memadai — tidak terlalu gelap, tidak silau berlebihan
5. Foto tidak buram (tidak blur atau goyang)
6. Foto bukan foto random, foto produk, pemandangan, hewan, atau konten tidak relevan
7. Orang berdiri, bukan duduk, jongkok, atau terlihat setengah badan
8. {$referenceRule}
9. {$handheldSideWarning}

Jawab HANYA dengan JSON tanpa markdown, tanpa teks lain:
{
  "valid": true atau false,
  "issues": ["masalah spesifik 1", "masalah spesifik 2"],
  "suggestion": "satu kalimat saran perbaikan jika tidak valid, atau string kosong jika valid"
}

Gunakan Bahasa Indonesia. Jika semua syarat terpenuhi, "valid" = true dan "issues" = [].
PROMPT;

        try {
            [$imageData, $mimeType] = $this->encodeImageForGroq($photo);

            $response = Http::timeout(20)
                ->withToken($this->apiKey)
                ->acceptJson()
                ->post($this->apiUrl, [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $prompt,
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => "data:{$mimeType};base64,{$imageData}",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'temperature' => 0.1,
                    'max_completion_tokens' => 300,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (!$response->successful()) {
                return ['valid' => true, 'issues' => [], 'suggestion' => ''];
            }

            $text   = $response->json('choices.0.message.content', '{}');
            // Bersihkan jika model masih menambahkan markdown
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

    /**
     * Groq membatasi base64 image request. Kompres ke JPEG ukuran sedang agar
     * validasi tetap stabil saat user upload foto kamera yang besar.
     */
    private function encodeImageForGroq(UploadedFile $photo): array
    {
        $raw = file_get_contents($photo->getPathname());
        $mimeType = $photo->getMimeType() ?: 'image/jpeg';

        if (!function_exists('imagecreatefromstring')) {
            return [base64_encode($raw), $mimeType];
        }

        $source = @imagecreatefromstring($raw);
        if (!$source) {
            return [base64_encode($raw), $mimeType];
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $maxDimension = 1400;
        $ratio = min(1, $maxDimension / max($width, $height));
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($target, null, 82);
        $compressed = ob_get_clean();

        return [base64_encode($compressed ?: $raw), 'image/jpeg'];
    }
}
