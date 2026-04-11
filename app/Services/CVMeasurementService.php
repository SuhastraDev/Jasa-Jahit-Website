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
     * Send body photo to FastAPI for measurement analysis.
     *
     * @param UploadedFile $photo Body photo file
     * @param string $refObject Reference object type (a4, atm, custom)
     * @param float|null $refWidthCm Custom ref width in cm
     * @param float|null $refHeightCm Custom ref height in cm
     * @return array Response from FastAPI
     */
    public function measure(UploadedFile $photo, string $refObject, ?float $refWidthCm = null, ?float $refHeightCm = null): array
    {
        try {
            $request = Http::timeout(60)
                ->attach('body_photo', $photo->getContent(), $photo->getClientOriginalName());

            $formData = ['ref_object' => $refObject];

            if ($refObject === 'custom') {
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
