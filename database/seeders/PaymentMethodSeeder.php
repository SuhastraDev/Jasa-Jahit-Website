<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Migrasikan konfigurasi DANA lama (tabel settings + file QR statis
     * storage/dana/qr_code.png) menjadi baris pertama di payment_methods,
     * supaya konfigurasi yang sudah diisi admin sebelumnya tidak hilang.
     */
    public function run(): void
    {
        if (PaymentMethod::where('name', 'DANA')->exists()) {
            return;
        }

        $danaNumber = Setting::get('dana_number');
        $danaName   = Setting::get('dana_name');

        if (!$danaNumber && !$danaName) {
            return;
        }

        $qrPath = null;
        if (Storage::disk('public')->exists('dana/qr_code.png')) {
            $qrPath = 'payment-methods/dana.png';
            Storage::disk('public')->copy('dana/qr_code.png', $qrPath);
        }

        PaymentMethod::create([
            'name'           => 'DANA',
            'account_number' => $danaNumber ?: '-',
            'account_name'   => $danaName ?: '-',
            'qr_image'       => $qrPath,
            'is_active'      => true,
        ]);
    }
}
