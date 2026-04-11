<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\RajaOngkirService;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    /**
     * Tampilkan halaman tracking resi dari sebuah order.
     */
    public function show(Order $order, RajaOngkirService $rajaOngkir)
    {
        // Pastikan order milik user yang login
        if ($order->user_id !== auth()->id()) {
            abort(403, 'Akses ditolak.');
        }

        // Pastikan order sudah ada shipment
        if (!$order->shipment) {
            return redirect()->route('user.orders.show', $order)
                ->with('error', 'Pesanan ini belum memiliki resi pengiriman.');
        }

        // Cek config apakah ada API config
        $hasKey = !empty(config('rajaongkir.api_key'));

        if ($hasKey) {
            $tracking = $rajaOngkir->trackShipment(
                $order->shipment->tracking_number,
                $order->shipment->expedition
            );
        } else {
            // Gunakan mock data jika API key tidak tersedia di .env
            $tracking = $rajaOngkir->getMockTrackingData(
                $order->shipment->tracking_number,
                $order->shipment->expedition
            );
        }

        return view('user.tracking.show', compact('order', 'tracking'));
    }
}
