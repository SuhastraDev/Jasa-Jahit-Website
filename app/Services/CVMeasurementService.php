<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;

class CVMeasurementService
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.cv.url', 'http://localhost:8000');
        $this->timeout = (int) config('services.cv.timeout', 120);
    }

    /**
     * Send front, side, and back photos to FastAPI for multi-view analysis.
     */
    public function measure(
        UploadedFile $frontPhoto,
        UploadedFile $sidePhoto,
        UploadedFile $backPhoto,
        string $refObject,
        ?float $refWidthCm = null,
        ?float $refHeightCm = null,
        string $referenceMode = 'fixed'
    ): array
    {
        try {
            [$frontContent, $frontName] = $this->preparePhotoForCv($frontPhoto, 'front.jpg');
            [$sideContent, $sideName] = $this->preparePhotoForCv($sidePhoto, 'side.jpg');
            [$backContent, $backName] = $this->preparePhotoForCv($backPhoto, 'back.jpg');

            $request = Http::timeout($this->timeout)
                ->attach('front_photo', $frontContent, $frontName)
                ->attach('side_photo', $sideContent, $sideName)
                ->attach('back_photo', $backContent, $backName);

            $formData = [
                'ref_object' => $refObject,
                'reference_mode' => $referenceMode,
            ];

            if ($refObject === 'custom' || $refWidthCm || $refHeightCm) {
                $formData['ref_width_cm'] = $refWidthCm;
                $formData['ref_height_cm'] = $refHeightCm;
            }

            $response = $request->post("{$this->baseUrl}/measure", $formData);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'error' => 'Server CV mengembalikan error: ' . $response->status(),
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'success' => false,
                'error' => 'Analisis foto terlalu lama atau layanan CV tidak merespons. Coba kompres foto, pastikan koneksi stabil, lalu ulangi proses.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check if the CV service is available.
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Resize foto sebelum dikirim ke service CV agar MediaPipe/OpenCV tidak
     * memproses foto kamera beresolusi sangat besar. Rasio tetap sama, jadi
     * skala marker dalam cm tetap konsisten.
     */
    private function preparePhotoForCv(UploadedFile $photo, string $fallbackName): array
    {
        $raw = $photo->getContent();
        $name = pathinfo($photo->getClientOriginalName() ?: $fallbackName, PATHINFO_FILENAME) . '.jpg';

        if (!function_exists('imagecreatefromstring')) {
            return [$raw, $photo->getClientOriginalName() ?: $fallbackName];
        }

        $source = @imagecreatefromstring($raw);
        if (!$source) {
            return [$raw, $photo->getClientOriginalName() ?: $fallbackName];
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $maxDimension = 1280;
        $ratio = min(1, $maxDimension / max($width, $height));

        if ($ratio >= 1) {
            return [$raw, $photo->getClientOriginalName() ?: $fallbackName];
        }

        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($target, null, 82);
        $compressed = ob_get_clean();

        return [$compressed ?: $raw, $name];
    }
}
