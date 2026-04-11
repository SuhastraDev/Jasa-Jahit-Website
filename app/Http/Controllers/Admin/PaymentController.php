<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\OrderStatus;
use App\Services\FonnteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Daftar pembayaran (filter by status)
     */
    public function index(Request $request)
    {
        $query = Payment::with(['order.user', 'order.service', 'verifiedBy'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->paginate(15)->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    /**
     * Verifikasi pembayaran → status payment 'verified', order 'confirmed'
     */
    public function verify(Payment $payment)
    {
        DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'verified',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);

            $payment->order->update(['status' => 'confirmed']);

            OrderStatus::create([
                'order_id' => $payment->order_id,
                'status' => 'confirmed',
                'note' => 'Pembayaran diverifikasi. Pesanan dikonfirmasi.',
                'changed_by' => auth()->id(),
            ]);
        });

        // Kirim notifikasi WA ke user
        $user = $payment->order->user;
        if ($user->phone) {
            try {
                $fonnte = new FonnteService();
                $fonnte->notifyPaymentVerified(
                    $user->phone,
                    $payment->order->order_code,
                    number_format($payment->amount, 0, ',', '.')
                );
            } catch (\Throwable $e) {
                Log::warning('[Fonnte] Gagal kirim notifikasi payment verified', [
                    'order_code' => $payment->order->order_code,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', 'Pembayaran berhasil diverifikasi. Pesanan #' . $payment->order->order_code . ' dikonfirmasi.');
    }

    /**
     * Tolak pembayaran → status payment 'rejected', simpan alasan
     */
    public function reject(Request $request, Payment $payment)
    {
        $request->validate([
            'reject_reason' => 'required|string|max:500',
        ]);

        $payment->update([
            'status' => 'rejected',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'reject_reason' => $request->reject_reason,
        ]);

        // Kirim notifikasi WA ke user
        $user = $payment->order->user;
        if ($user->phone) {
            try {
                $fonnte = new FonnteService();
                $fonnte->notifyPaymentRejected(
                    $user->phone,
                    $payment->order->order_code,
                    $request->reject_reason
                );
            } catch (\Throwable $e) {
                Log::warning('[Fonnte] Gagal kirim notifikasi payment rejected', [
                    'order_code' => $payment->order->order_code,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', 'Pembayaran ditolak. User akan diinformasikan.');
    }
}
