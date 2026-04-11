<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Services\FonnteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Daftar semua pesanan (dengan filter status)
     */
    public function index(Request $request)
    {
        $query = Order::with(['user', 'service', 'latestPayment'])->latest();

        // Filter berdasarkan status jika ada
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Pencarian berdasarkan kode pesanan atau nama user
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        $statusOptions = ['pending', 'confirmed', 'processing', 'done', 'shipped', 'completed', 'cancelled'];

        return view('admin.orders.index', compact('orders', 'statusOptions'));
    }

    /**
     * Detail pesanan admin — lihat semua data + form update
     */
    public function show(Order $order)
    {
        $order->load(['user', 'service', 'catalog', 'measurement', 'statuses.changedBy', 'latestPayment', 'payments', 'shipment']);

        $statusOptions = ['pending', 'confirmed', 'processing', 'done', 'shipped', 'completed', 'cancelled'];

        return view('admin.orders.show', compact('order', 'statusOptions'));
    }

    /**
     * Update status pesanan + simpan log
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,done,shipped,completed,cancelled',
            'note' => 'nullable|string|max:500',
            'total_price' => 'nullable|numeric|min:0',
        ]);

        // Harga wajib diisi saat mengkonfirmasi pesanan
        if ($request->status === 'confirmed' && !$request->filled('total_price') && !$order->total_price) {
            return back()
                ->withErrors(['total_price' => 'Total harga harus diisi saat mengkonfirmasi pesanan.'])
                ->withInput();
        }

        $order->update([
            'status' => $request->status,
            'total_price' => $request->total_price ?? $order->total_price,
            'notes' => $request->note ?? $order->notes,
        ]);

        // Catat perubahan status di order_statuses
        OrderStatus::create([
            'order_id' => $order->id,
            'status' => $request->status,
            'note' => $request->note,
            'changed_by' => auth()->id(),
        ]);

        // Kirim notifikasi WA ke user
        if ($order->user->phone) {
            try {
                $fonnte = new FonnteService();
                $fonnte->notifyStatusChanged(
                    $order->user->phone,
                    $order->order_code,
                    $order->status_label
                );
            } catch (\Throwable $e) {
                Log::warning('[Fonnte] Gagal kirim notifikasi status changed', [
                    'order_code' => $order->order_code,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Status pesanan berhasil diperbarui menjadi: ' . $order->status_label);
    }
}
