<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RajaOngkirService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('rajaongkir.api_key');
        $this->baseUrl = config('rajaongkir.base_url');
    }

    /**
     * Lacak resi pengiriman menggunakan endpoint /waybill (POST).
     *
     * @param string $waybill Nomor Resi
     * @param string $courier Kode ekspedisi (jne, pos, tiki)
     * @return array
     */
    public function trackShipment(string $waybill, string $courier): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'API Key RajaOngkir belum dikonfigurasi di .env',
                'is_mock' => true // Helper for local dev without key
            ];
        }

        try {
            $response = Http::withHeaders([
                'key' => $this->apiKey,
                'content-type' => 'application/x-www-form-urlencoded'
            ])->asForm()->post("{$this->baseUrl}/waybill", [
                'waybill' => $waybill,
                'courier' => strtolower($courier),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Rajaongkir returns 200 even for errors in their API (e.g. invalid key/waybill)
                // Need to check the status code payload
                $status = $data['rajaongkir']['status']['code'] ?? 500;
                
                if ($status !== 200) {
                    return [
                        'success' => false,
                        'error' => $data['rajaongkir']['status']['description'] ?? 'Gagal mengambil data resi. Cek kembali nomor resi dan kurir.',
                        'data' => null
                    ];
                }

                return [
                    'success' => true,
                    'error' => null,
                    'data' => $data['rajaongkir']['result']
                ];
            }

            return [
                'success' => false,
                'error' => 'Gagal terhubung ke API RajaOngkir (' . $response->status() . ')',
                'data' => null
            ];

        } catch (\Exception $e) {
            Log::error('RajaOngkir Tracking Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Terjadi kesalahan sistem saat melacak resi.',
                'data' => null
            ];
        }
    }

    /**
     * Dapatkan simulasi resi untuk keperluan testing lokal jika tidak ada API key
     */
    public function getMockTrackingData(string $waybill, string $courier): array
    {
        return [
            'success' => true,
            'is_mock' => true,
            'summary' => [
                'courier_code' => $courier,
                'courier_name' => strtoupper($courier),
                'waybill_number' => $waybill,
                'status' => 'DELIVERED',
                'origin' => 'JAKARTA',
                'destination' => 'SURABAYA',
                'receiver_name' => 'John Doe'
            ],
            'details' => [
                'waybill_date' => now()->subDays(2)->format('Y-m-d'),
                'waybill_time' => '10:00:00',
                'shipped_date' => now()->subDays(2)->format('Y-m-d'),
            ],
            'manifest' => [
                [
                    'manifest_code' => 'DELIVERED',
                    'manifest_description' => 'Paket diterima oleh John Doe',
                    'manifest_date' => now()->format('Y-m-d'),
                    'manifest_time' => now()->format('H:i:s'),
                    'city_name' => 'SURABAYA'
                ],
                [
                    'manifest_code' => 'WITH DELIVERY COURIER',
                    'manifest_description' => 'Paket dibawa kurir menuju alamat tujuan',
                    'manifest_date' => now()->format('Y-m-d'),
                    'manifest_time' => now()->subHours(2)->format('H:i:s'),
                    'city_name' => 'SURABAYA'
                ],
                [
                    'manifest_code' => 'RECEIVED AT WAREHOUSE',
                    'manifest_description' => 'Paket tiba di gudang tujuan',
                    'manifest_date' => now()->subDay()->format('Y-m-d'),
                    'manifest_time' => '15:30:00',
                    'city_name' => 'SURABAYA'
                ]
            ]
        ];
    }
}
