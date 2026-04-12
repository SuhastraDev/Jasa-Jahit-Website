<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BuyerShipment;
use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BuyerShipmentController extends Controller
{
    /**
     * User menginput resi pengiriman barang ke penjual (permak)
     */
    public function store(Request $request, Order $order)
    {
        if ((int) $order->user_id !== (int) auth()->id()) abort(403);

        if ($order->service_type !== 'permak') {
            return back()->with('error', 'Fitur ini hanya untuk layanan permak.');
        }

        if ($order->status !== 'confirmed') {
            return back()->with('error', 'Pesanan belum siap untuk pengiriman barang.');
        }

        $request->validate([
            'expedition'      => 'required|string|max:50',
            'tracking_number' => 'required|string|max:100',
            'proof_image'     => 'nullable|image|max:4096',
            'notes'           => 'nullable|string|max:500',
        ]);

        $proofPath = null;
        if ($request->hasFile('proof_image')) {
            $proofPath = $request->file('proof_image')->store('buyer-shipments/' . $order->id, 'public');
        }

        // Hapus data lama jika ada (re-upload)
        if ($order->buyerShipment) {
            if ($order->buyerShipment->proof_image) {
                Storage::disk('public')->delete($order->buyerShipment->proof_image);
            }
            $order->buyerShipment->delete();
        }

        BuyerShipment::create([
            'order_id'        => $order->id,
            'expedition'      => $request->expedition,
            'tracking_number' => $request->tracking_number,
            'proof_image'     => $proofPath,
            'notes'           => $request->notes,
            'shipped_at'      => now(),
        ]);

        $order->update(['status' => 'waiting_item']);

        OrderStatus::create([
            'order_id'   => $order->id,
            'status'     => 'waiting_item',
            'note'       => "Pelanggan mengirim barang via {$request->expedition}, resi: {$request->tracking_number}",
            'changed_by' => auth()->id(),
        ]);

        return redirect()
            ->route('user.orders.show', $order)
            ->with('success', 'Informasi pengiriman barang berhasil dikirim! Penjual akan mengkonfirmasi setelah barang diterima.');
    }
}
