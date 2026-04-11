<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteService
{
    protected string $token;
    protected string $apiUrl = 'https://api.fonnte.com/send';

    public function __construct()
    {
        $this->token = config('services.fonnte.token', '');
    }

    /**
     * Kirim pesan WhatsApp via Fonnte API.
     *
     * @param string $phone  Nomor HP format 628xxxxxxxxxx
     * @param string $message Isi pesan
     */
    public function send(string $phone, string $message): bool
    {
        if (empty($this->token)) {
            Log::warning('[Fonnte] Token tidak dikonfigurasi. Pesan tidak terkirim.', [
                'phone'   => $phone,
                'message' => $message,
            ]);
            return false;
        }

        // Normalisasi nomor HP: +62 → 62, 08 → 628
        $phone = $this->normalizePhone($phone);

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])->post($this->apiUrl, [
                'target'  => $phone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                Log::info('[Fonnte] Pesan berhasil dikirim', ['phone' => $phone]);
                return true;
            }

            Log::error('[Fonnte] Gagal mengirim pesan', [
                'phone'    => $phone,
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('[Fonnte] Exception saat mengirim pesan', [
                'phone'   => $phone,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Normalisasi nomor HP ke format 628xxxxxxxxxx.
     */
    protected function normalizePhone(string $phone): string
    {
        // Hapus spasi, dash, dan karakter non-digit
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Jika diawali 0, ganti dengan 62
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return $phone;
    }

    // ── Template Pesan ──

    public function notifyNewOrder(string $phone, string $orderCode): bool
    {
        return $this->send($phone, "🧵 *Pesanan Baru #{$orderCode}*\n\nTerima kasih telah melakukan pemesanan di ZrintTailor! Pesanan Anda sedang kami proses.\n\nSilakan tunggu konfirmasi dari admin.");
    }

    public function notifyPaymentVerified(string $phone, string $orderCode, string $amount): bool
    {
        return $this->send($phone, "✅ *Pembayaran Terverifikasi*\n\nPembayaran sebesar Rp {$amount} untuk pesanan #{$orderCode} telah diverifikasi.\n\nPesanan Anda segera diproses. Terima kasih!");
    }

    public function notifyPaymentRejected(string $phone, string $orderCode, string $reason): bool
    {
        return $this->send($phone, "❌ *Pembayaran Ditolak*\n\nPembayaran untuk pesanan #{$orderCode} ditolak.\n\nAlasan: {$reason}\n\nSilakan upload ulang bukti pembayaran yang valid.");
    }

    public function notifyStatusChanged(string $phone, string $orderCode, string $statusLabel): bool
    {
        return $this->send($phone, "📋 *Update Pesanan #{$orderCode}*\n\nStatus pesanan Anda diperbarui menjadi: *{$statusLabel}*\n\nLogin ke website untuk melihat detail.");
    }

    public function notifyShipment(string $phone, string $orderCode, string $expedition, string $trackingNumber): bool
    {
        return $this->send($phone, "📦 *Pesanan #{$orderCode} Telah Dikirim!*\n\nEkspedisi: {$expedition}\nNo. Resi: {$trackingNumber}\n\nAnda bisa melacak pengiriman melalui website kami.");
    }
}
