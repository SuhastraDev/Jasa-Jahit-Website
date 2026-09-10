<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
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

        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get();

        return view('user.payment.upload', compact('order', 'paymentMethods'));
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
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount' => 'required|numeric|min:1000',
            'proof_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $proofPath = $request->file('proof_image')
            ->store('payments/' . $order->id, 'public');

        Payment::create([
            'order_id' => $order->id,
            'payment_method_id' => $request->payment_method_id,
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
