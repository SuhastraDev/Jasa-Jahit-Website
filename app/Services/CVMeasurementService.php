<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;

class CVMeasurementService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.cv.url', 'http://localhost:8000');
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
        ?float $refHeightCm = null
    ): array
    {
        try {
            $request = Http::timeout(60)
                ->attach('front_photo', $frontPhoto->getContent(), $frontPhoto->getClientOriginalName())
                ->attach('side_photo', $sidePhoto->getContent(), $sidePhoto->getClientOriginalName())
                ->attach('back_photo', $backPhoto->getContent(), $backPhoto->getClientOriginalName());

            $formData = ['ref_object' => $refObject];

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
                'error' => 'Tidak dapat terhubung ke layanan CV. Pastikan server Python FastAPI berjalan di ' . $this->baseUrl,
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
