<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Services\FonnteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'service', 'latestPayment'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

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

        $statusOptions = [
            'pending', 'confirmed', 'waiting_item', 'item_received',
            'processing', 'done', 'shipped', 'completed', 'cancelled'
        ];

        return view('admin.orders.index', compact('orders', 'statusOptions'));
    }

    public function show(Order $order)
    {
        $order->load([
            'user', 'service', 'catalog', 'measurement',
            'statuses.changedBy', 'latestPayment', 'payments',
            'shipment', 'buyerShipment'
        ]);

        $serviceType = $order->service_type;

        // Status yang tersedia sesuai tipe layanan
        $statusOptions = match ($serviceType) {
            'design' => ['pending', 'confirmed', 'processing', 'done', 'completed', 'cancelled'],
            'permak' => ['pending', 'confirmed', 'waiting_item', 'item_received', 'processing', 'done', 'shipped', 'completed', 'cancelled'],
            default  => ['pending', 'confirmed', 'processing', 'done', 'shipped', 'completed', 'cancelled'],
        };

        return view('admin.orders.show', compact('order', 'statusOptions', 'serviceType'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status'      => 'required|in:pending,confirmed,waiting_item,item_received,processing,done,shipped,completed,cancelled',
            'note'        => 'nullable|string|max:500',
            'total_price' => 'nullable|numeric|min:1',
        ]);

        $price = $request->total_price ? (int) $request->total_price : null;

        if ($request->status === 'confirmed' && !$price && !$order->total_price) {
            return back()
                ->withErrors(['total_price' => 'Total harga harus diisi saat mengkonfirmasi pesanan.'])
                ->withInput();
        }

        $order->update([
            'status'      => $request->status,
            'total_price' => $price ?? $order->total_price,
            'notes'       => $request->note ?? $order->notes,
        ]);

        OrderStatus::create([
            'order_id'   => $order->id,
            'status'     => $request->status,
            'note'       => $request->note,
            'changed_by' => auth()->id(),
        ]);

        $this->sendWaNotification($order);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Status pesanan berhasil diperbarui menjadi: ' . $order->fresh()->status_label);
    }

    /**
     * Konfirmasi barang permak sudah diterima dari pembeli
     */
    public function confirmItemReceived(Request $request, Order $order)
    {
        if ($order->status !== 'waiting_item') {
            return back()->with('error', 'Pesanan tidak dalam status menunggu kiriman barang.');
        }

        $request->validate(['note' => 'nullable|string|max:500']);

        $order->update(['status' => 'item_received']);

        OrderStatus::create([
            'order_id'   => $order->id,
            'status'     => 'item_received',
            'note'       => $request->note ?: 'Barang dari pembeli sudah diterima. Siap diproses.',
            'changed_by' => auth()->id(),
        ]);

        $this->sendWaNotification($order);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Konfirmasi penerimaan barang berhasil.');
    }

    /**
     * Upload file desain untuk layanan Desain Digital
     */
    public function uploadDesignFile(Request $request, Order $order)
    {
        if ($order->service_type !== 'design') {
            return back()->with('error', 'Fitur ini hanya untuk layanan desain.');
        }
        if (!in_array($order->status, ['processing', 'revision'])) {
            return back()->with('error', 'Upload file hanya bisa dilakukan saat status sedang diproses atau revisi.');
        }

        $request->validate([
            'design_file'  => 'required|file|mimes:jpg,jpeg,png,webp,pdf,zip,rar|max:20480',
            'design_notes' => 'nullable|string|max:1000',
        ]);

        // Hapus file lama jika ada
        if ($order->design_file) {
            Storage::disk('public')->delete($order->design_file);
        }

        $path = $request->file('design_file')->store('orders/designs', 'public');

        $isRevision = $order->status === 'revision';

        $order->update([
            'design_file'  => $path,
            'design_notes' => $request->design_notes,
            'status'       => 'done',
        ]);

        OrderStatus::create([
            'order_id'   => $order->id,
            'status'     => 'done',
            'note'       => $isRevision
                ? "File revisi ke-{$order->revision_count} telah diunggah dan siap diunduh oleh pelanggan."
                : 'File desain telah diunggah dan siap diunduh oleh pelanggan.',
            'changed_by' => auth()->id(),
        ]);

        $this->sendWaNotification($order);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', $isRevision
                ? "File revisi ke-{$order->revision_count} berhasil diunggah."
                : 'File desain berhasil diunggah. Pelanggan dapat mengunduhnya sekarang.');
    }

    private function sendWaNotification(Order $order): void
    {
        if (!$order->user?->phone) return;
        try {
            $fonnte = new FonnteService();
            $fonnte->notifyStatusChanged(
                $order->user->phone,
                $order->order_code,
                $order->fresh()->status_label
            );
        } catch (\Throwable $e) {
            Log::warning('[Fonnte] Gagal kirim notifikasi', [
                'order_code' => $order->order_code,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
