<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Measurement;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Service;
use App\Models\Catalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    /**
     * Daftar pesanan milik user
     */
    public function index()
    {
        $orders = Order::where('user_id', auth()->id())
            ->with('service')
            ->latest()
            ->paginate(10);

        return view('user.orders.index', compact('orders'));
    }

    /**
     * Halaman buat pesanan baru — pilih layanan + isi form
     */
    public function create()
    {
        $services     = Service::where('is_active', true)->get();
        $catalogs     = Catalog::where('is_active', true)->with('service')->get();
        $measurements = Measurement::where('user_id', auth()->id())->latest()->get();

        return view('user.orders.create', compact('services', 'catalogs', 'measurements'));
    }

    /**
     * Simpan pesanan baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'service_id'        => 'required|exists:services,id',
            'clothing_type'     => 'required|string|max:100',
            'color'             => 'nullable|string|max:100',
            'material'          => 'nullable|string|max:100',
            'description'       => 'nullable|string|max:1000',
            'catalog_id'        => 'nullable|exists:catalogs,id',
            'measurement_id'    => 'nullable|exists:measurements,id',
            'reference_image'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            // Alamat
            'province'          => 'required|string|max:100',
            'city'              => 'required|string|max:100',
            'district'          => 'required|string|max:100',
            'village'           => 'nullable|string|max:100',
            'postal_code'       => 'nullable|string|max:10',
            'rt'                => 'nullable|string|max:5',
            'rw'                => 'nullable|string|max:5',
            'detail_address'    => 'required|string|max:500',
            'recipient_phone'   => 'nullable|string|max:20',
            // Ukuran manual (opsional, untuk Custom tanpa CV)
            'manual_chest'          => 'nullable|numeric|min:1|max:300',
            'manual_waist'          => 'nullable|numeric|min:1|max:300',
            'manual_hips'           => 'nullable|numeric|min:1|max:300',
            'manual_shoulder_width' => 'nullable|numeric|min:1|max:200',
            'manual_arm_length'     => 'nullable|numeric|min:1|max:200',
            'manual_height'         => 'nullable|numeric|min:1|max:300',
        ]);

        $service = Service::findOrFail($request->service_id);
        $measurementId = $request->measurement_id ?: null;

        // Layanan Custom: wajib punya ukuran badan (CV atau manual)
        if (strtolower($service->name) === 'custom') {
            $hasCV     = !empty($request->measurement_id);
            $hasManual = !empty($request->manual_chest) && !empty($request->manual_waist);

            if (!$hasCV && !$hasManual) {
                return back()->withErrors([
                    'measurement_id' => 'Layanan Custom memerlukan data ukuran badan. Pilih dari data CV atau isi ukuran manual.',
                ])->withInput();
            }

            // Jika CV: pastikan milik user yang login
            if ($hasCV) {
                $measurement = Measurement::where('id', $request->measurement_id)
                    ->where('user_id', auth()->id())->first();
                if (!$measurement) {
                    return back()->withErrors(['measurement_id' => 'Data ukuran badan tidak valid.'])->withInput();
                }
            }

            // Jika manual: simpan sebagai Measurement baru
            if (!$hasCV && $hasManual) {
                $newMeasurement = Measurement::create([
                    'user_id'        => auth()->id(),
                    'chest'          => $request->manual_chest,
                    'waist'          => $request->manual_waist,
                    'hips'           => $request->manual_hips ?? 0,
                    'shoulder_width' => $request->manual_shoulder_width ?? 0,
                    'arm_length'     => $request->manual_arm_length ?? 0,
                    'height'         => $request->manual_height ?? 0,
                    'ref_object'     => 'manual',
                    'ref_size'       => null,
                    'is_edited'      => true,
                ]);
                $measurementId = $newMeasurement->id;
            }
        }

        // Bangun string alamat lengkap dari field terstruktur
        $addressParts = array_filter([
            $request->detail_address,
            $request->rt && $request->rw ? "RT {$request->rt}/RW {$request->rw}" : ($request->rt ? "RT {$request->rt}" : null),
            $request->village,
            $request->district,
            $request->city,
            $request->province,
            $request->postal_code,
        ]);
        $fullAddress = implode(', ', $addressParts);

        $data = [
            'user_id'        => auth()->id(),
            'service_id'     => $request->service_id,
            'catalog_id'     => $request->catalog_id ?: null,
            'measurement_id' => $measurementId,
            'order_code'     => Order::generateOrderCode(),
            'clothing_type'  => $request->clothing_type,
            'color'          => $request->color,
            'material'       => $request->material,
            'description'    => $request->description,
            'address'        => $fullAddress,
            'province'       => $request->province,
            'city'           => $request->city,
            'district'       => $request->district,
            'village'        => $request->village,
            'postal_code'    => $request->postal_code,
            'rt'             => $request->rt,
            'rw'             => $request->rw,
            'detail_address'  => $request->detail_address,
            'recipient_phone' => $request->recipient_phone ?: auth()->user()->phone,
            'status'          => 'pending',
        ];

        if ($request->hasFile('reference_image')) {
            $data['reference_image'] = $request->file('reference_image')
                ->store('orders/references', 'public');
        }

        $order = Order::create($data);

        // Catat status pertama di tabel order_statuses
        OrderStatus::create([
            'order_id' => $order->id,
            'status' => 'pending',
            'note' => 'Pesanan baru dibuat oleh pelanggan.',
            'changed_by' => auth()->id(),
        ]);

        return redirect()
            ->route('user.orders.show', $order)
            ->with('success', 'Pesanan berhasil dibuat! Kode pesanan Anda: ' . $order->order_code);
    }

    /**
     * Detail pesanan + riwayat status
     */
    public function show(Order $order)
    {
        // Pastikan user hanya bisa melihat pesanannya sendiri
        if ((int) $order->user_id !== (int) auth()->id()) {
            abort(403, 'Anda tidak memiliki akses ke pesanan ini.');
        }

        $order->load(['service', 'catalog', 'measurement', 'statuses', 'latestPayment', 'shipment']);

        return view('user.orders.show', compact('order'));
    }

    /**
     * User konfirmasi sudah menerima barang → order completed
     */
    public function confirmReceipt(Order $order)
    {
        if ((int) $order->user_id !== (int) auth()->id()) abort(403);
        if ($order->status !== 'shipped') {
            return back()->with('error', 'Konfirmasi hanya bisa dilakukan saat status pesanan "Dikirim".');
        }

        $order->update(['status' => 'completed']);

        OrderStatus::create([
            'order_id'   => $order->id,
            'status'     => 'completed',
            'note'       => 'Pelanggan mengkonfirmasi sudah menerima pesanan.',
            'changed_by' => auth()->id(),
        ]);

        // Notifikasi ke chat
        $chat = Chat::firstOrCreate(
            ['user_id' => auth()->id()],
            ['last_message_at' => now()]
        );

        $chat->messages()->create([
            'sender_id' => auth()->id(),
            'type'      => 'text',
            'content'   => "✅ Saya sudah menerima pesanan #{$order->order_code} ({$order->clothing_type}). Terima kasih!",
            'is_read'   => false,
        ]);

        $chat->update(['last_message_at' => now()]);

        return redirect()
            ->route('user.orders.show', $order)
            ->with('success', 'Terima kasih! Pesanan dinyatakan selesai. Jangan lupa berikan ulasan.');
    }

    /**
     * User melaporkan masalah pengiriman (barang tidak diterima)
     */
    public function reportIssue(Request $request, Order $order)
    {
        if ((int) $order->user_id !== (int) auth()->id()) abort(403);
        if ($order->status !== 'shipped') {
            return back()->with('error', 'Laporan hanya bisa dikirim saat status pesanan "Dikirim".');
        }

        $request->validate([
            'issue_note' => 'required|string|max:500',
        ]);

        OrderStatus::create([
            'order_id'   => $order->id,
            'status'     => 'shipped',
            'note'       => '[LAPORAN MASALAH] ' . $request->issue_note,
            'changed_by' => auth()->id(),
        ]);

        // Kirim otomatis ke chat room user → admin
        $chat = Chat::firstOrCreate(
            ['user_id' => auth()->id()],
            ['last_message_at' => now()]
        );

        $chatContent = "⚠️ *Laporan Masalah Pengiriman*\n"
            . "Pesanan: #{$order->order_code}\n"
            . "Layanan: {$order->service->name} — {$order->clothing_type}\n\n"
            . $request->issue_note;

        $chat->messages()->create([
            'sender_id' => auth()->id(),
            'type'      => 'text',
            'content'   => $chatContent,
            'is_read'   => false,
        ]);

        $chat->update(['last_message_at' => now()]);

        return back()->with('success', 'Laporan masalah berhasil dikirim ke admin via pesan. Admin akan segera menghubungi Anda.');
    }
}
