<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\FonnteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShipmentController extends Controller
{
    /**
     * Simpan resi pengiriman untuk pesanan tertentu.
     */
    public function store(Request $request, Order $order)
    {
        $request->validate([
            'expedition' => 'required|string|max:100',
            'tracking_number' => 'required|string|max:100',
        ]);

        if (in_array($order->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'Pesanan ini sudah selesai atau dibatalkan, tidak dapat diinput resi.');
        }

        try {
            DB::transaction(function () use ($request, $order) {
                // 1. Simpan data pengiriman
                Shipment::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'expedition' => $request->expedition,
                        'tracking_number' => $request->tracking_number,
                        'shipped_at' => now(),
                    ]
                );

                // 2. Update status order menjadi shipped
                if ($order->status !== 'shipped') {
                    $order->update(['status' => 'shipped']);

                    // 3. Catat di order history
                    $order->statuses()->create([
                        'status' => 'shipped',
                        'notes' => "Pesanan dikirim menggunakan kurir {$request->expedition} dengan resi {$request->tracking_number}.",
                        'changed_by' => auth()->id(),
                    ]);
                }
            });

            // Kirim notifikasi WA ke user
            $user = $order->user;
            if ($user->phone) {
                try {
                    $fonnte = new FonnteService();
                    $fonnte->notifyShipment(
                        $user->phone,
                        $order->order_code,
                        strtoupper($request->expedition),
                        $request->tracking_number
                    );
                } catch (\Throwable $e) {
                    Log::warning('[Fonnte] Gagal kirim notifikasi shipment', [
                        'order_code' => $order->order_code,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }

            return back()->with('success', 'Berhasil menginput resi pengiriman. Status pesanan diupdate ke Shipped.');

        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan sistem saat menyimpan resi: ' . $e->getMessage());
        }
    }
}
