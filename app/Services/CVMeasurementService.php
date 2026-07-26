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
            $request = Http::timeout($this->timeout)
                ->attach('front_photo', $frontPhoto->getContent(), $frontPhoto->getClientOriginalName())
                ->attach('side_photo', $sidePhoto->getContent(), $sidePhoto->getClientOriginalName())
                ->attach('back_photo', $backPhoto->getContent(), $backPhoto->getClientOriginalName());

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
}
