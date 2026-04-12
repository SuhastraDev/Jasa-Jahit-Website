<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Halaman upload bukti pembayaran
     */
    public function create(Order $order)
    {
        // Pastikan user hanya bisa bayar pesanannya sendiri
        if ((int) $order->user_id !== (int) auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        // Cek apakah sudah ada pembayaran yang pending atau verified
        $existingPayment = $order->payments()
            ->whereIn('status', ['pending', 'verified'])
            ->first();

        if ($existingPayment) {
            return redirect()
                ->route('user.orders.show', $order)
                ->with('info', 'Pembayaran sudah diupload. Silakan tunggu verifikasi admin.');
        }

        // Pastikan pesanan memiliki total_price
        if (!$order->total_price) {
            return redirect()
                ->route('user.orders.show', $order)
                ->with('info', 'Harga pesanan belum ditentukan oleh admin. Silakan tunggu.');
        }

        $danaNumber  = config('services.dana.number');
        $danaName    = config('services.dana.name');
        $danaQrExists = \Illuminate\Support\Facades\Storage::disk('public')->exists('dana/qr_code.png');

        return view('user.payment.upload', compact('order', 'danaNumber', 'danaName', 'danaQrExists'));
    }

    /**
     * Simpan bukti pembayaran
     */
    public function store(Request $request, Order $order)
    {
        if ((int) $order->user_id !== (int) auth()->id()) {
            abort(403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'proof_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $proofPath = $request->file('proof_image')
            ->store('payments/' . $order->id, 'public');

        Payment::create([
            'order_id' => $order->id,
            'amount' => $request->amount,
            'payment_type' => 'full',
            'proof_image' => $proofPath,
            'status' => 'pending',
        ]);

        return redirect()
            ->route('user.orders.show', $order)
            ->with('success', 'Bukti pembayaran berhasil diupload! Menunggu verifikasi admin.');
    }
}
