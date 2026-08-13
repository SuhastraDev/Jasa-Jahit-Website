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
        string $referenceMode = 'fixed',
        array $referenceBoxes = []
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

            foreach (['front', 'side', 'back'] as $view) {
                if (!empty($referenceBoxes[$view])) {
                    $formData["{$view}_reference_box"] = $referenceBoxes[$view];
                }
            }

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
     * Upload the photos once and let FastAPI process them in the background.
     */
    public function startMeasurementJob(
        UploadedFile $frontPhoto,
        UploadedFile $sidePhoto,
        UploadedFile $backPhoto,
        string $refObject,
        ?float $refWidthCm = null,
        ?float $refHeightCm = null,
        string $referenceMode = 'fixed',
        array $referenceBoxes = []
    ): array
    {
        try {
            [$frontContent, $frontName] = $this->preparePhotoForCv($frontPhoto, 'front.jpg');
            [$sideContent, $sideName] = $this->preparePhotoForCv($sidePhoto, 'side.jpg');
            [$backContent, $backName] = $this->preparePhotoForCv($backPhoto, 'back.jpg');

            $request = Http::timeout(35)
                ->attach('front_photo', $frontContent, $frontName)
                ->attach('side_photo', $sideContent, $sideName)
                ->attach('back_photo', $backContent, $backName);

            $formData = [
                'ref_object' => $refObject,
                'reference_mode' => $referenceMode,
                'ref_width_cm' => $refWidthCm,
                'ref_height_cm' => $refHeightCm,
            ];

            foreach (['front', 'side', 'back'] as $view) {
                if (!empty($referenceBoxes[$view])) {
                    $formData["{$view}_reference_box"] = $referenceBoxes[$view];
                }
            }

            $response = $request->post("{$this->baseUrl}/measure/jobs", $formData);
            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'error' => 'Layanan CV gagal memulai analisis: ' . $response->status(),
            ];
        } catch (\Illuminate\Http\Client\ConnectionException) {
            return [
                'success' => false,
                'error' => 'Layanan CV tidak merespons saat foto dikirim. Periksa koneksi lalu coba lagi.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Analisis tidak dapat dimulai: ' . $e->getMessage(),
            ];
        }
    }

    public function measurementJobStatus(string $jobId): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/measure/jobs/{$jobId}");
            if ($response->successful()) {
                return $response->json();
            }

            return [
                'status' => 'failed',
                'error' => $response->status() === 404
                    ? 'Proses analisis tidak ditemukan atau sudah kedaluwarsa.'
                    : 'Status analisis tidak dapat dibaca.',
            ];
        } catch (\Throwable) {
            return [
                'status' => 'waiting',
                'progress' => [
                    'stage' => 'reconnecting',
                    'percent' => 5,
                    'message' => 'Menghubungkan kembali ke layanan analisis',
                    'view' => null,
                ],
            ];
        }
    }

    /**
     * Kirim 1 foto pakaian (baju/celana) rata di lantai + marker referensi
     * ke FastAPI untuk analisis flat-lay. Sinkron — tidak ada model pose
     * yang dipanggil, jauh lebih ringan/cepat dari measure()/startMeasurementJob().
     */
    public function measureGarment(
        UploadedFile $photo,
        string $garmentType,
        string $refObject,
        ?float $refWidthCm = null,
        ?float $refHeightCm = null,
        ?string $referenceBox = null
    ): array
    {
        try {
            [$content, $name] = $this->preparePhotoForCv($photo, "{$garmentType}.jpg");

            $request = Http::timeout($this->timeout)->attach('photo', $content, $name);

            $formData = [
                'garment_type' => $garmentType,
                'ref_object' => $refObject,
            ];

            if ($refObject === 'custom' || $refWidthCm || $refHeightCm) {
                $formData['ref_width_cm'] = $refWidthCm;
                $formData['ref_height_cm'] = $refHeightCm;
            }

            if (!empty($referenceBox)) {
                $formData['reference_box'] = $referenceBox;
            }

            $response = $request->post("{$this->baseUrl}/measure/garment", $formData);

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
