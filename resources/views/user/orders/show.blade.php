@extends('layouts.user')
@section('page-title', 'Detail Pesanan')
@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('user.orders.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Pesanan Saya
            </a>
            <h1 class="text-xl font-bold text-gray-900 mt-1 font-mono">{{ $order->order_code }}</h1>
        </div>
        @php
            $colors = [
                'yellow' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                'blue' => 'bg-blue-100 text-blue-700 border-blue-200',
                'indigo' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                'purple' => 'bg-purple-100 text-purple-700 border-purple-200',
                'orange' => 'bg-orange-100 text-orange-700 border-orange-200',
                'green' => 'bg-green-100 text-green-700 border-green-200',
                'red' => 'bg-red-100 text-red-700 border-red-200',
                'gray' => 'bg-gray-100 text-gray-600 border-gray-200',
            ];
        @endphp
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold rounded-full border {{ $colors[$order->status_color] ?? 'bg-gray-100 text-gray-600 border-gray-200' }}">
            {{ $order->status_label }}
        </span>
    </div>

    {{-- Banner Langkah Selanjutnya --}}
    @php
        $bannerConfig = match($order->status) {
            'pending'    => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-200', 'icon_bg' => 'bg-yellow-100', 'icon_color' => 'text-yellow-600', 'title_color' => 'text-yellow-800', 'text_color' => 'text-yellow-700',
                'title' => 'Menunggu Konfirmasi Admin', 'text' => 'Pesanan Anda sedang ditinjau. Admin akan segera menentukan harga dan mengonfirmasi pesanan.',
                'icon' => 'clock'],
            'confirmed'  => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'icon_bg' => 'bg-blue-100', 'icon_color' => 'text-blue-600', 'title_color' => 'text-blue-800', 'text_color' => 'text-blue-700',
                'title' => 'Silakan Lakukan Pembayaran', 'text' => 'Pesanan dikonfirmasi! Selesaikan pembayaran agar pengerjaan bisa segera dimulai.',
                'icon' => 'payment'],
            'processing' => ['bg' => 'bg-indigo-50', 'border' => 'border-indigo-200', 'icon_bg' => 'bg-indigo-100', 'icon_color' => 'text-indigo-600', 'title_color' => 'text-indigo-800', 'text_color' => 'text-indigo-700',
                'title' => 'Pakaian Sedang Dijahit', 'text' => 'Proses penjahitan sedang berlangsung. Kami akan memberitahu Anda setelah selesai.',
                'icon' => 'scissors'],
            'done'       => ['bg' => 'bg-purple-50', 'border' => 'border-purple-200', 'icon_bg' => 'bg-purple-100', 'icon_color' => 'text-purple-600', 'title_color' => 'text-purple-800', 'text_color' => 'text-purple-700',
                'title' => 'Pakaian Selesai Dijahit!', 'text' => 'Pakaian Anda sudah selesai dan akan segera dikirimkan. Pantau nomor resi di sini.',
                'icon' => 'check'],
            'shipped'    => ['bg' => 'bg-orange-50', 'border' => 'border-orange-200', 'icon_bg' => 'bg-orange-100', 'icon_color' => 'text-orange-600', 'title_color' => 'text-orange-800', 'text_color' => 'text-orange-700',
                'title' => 'Pesanan Sedang Dikirim', 'text' => 'Lacak paket Anda menggunakan nomor resi di bawah.',
                'icon' => 'truck'],
            'completed'  => ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'icon_bg' => 'bg-green-100', 'icon_color' => 'text-green-600', 'title_color' => 'text-green-800', 'text_color' => 'text-green-700',
                'title' => 'Pesanan Selesai!', 'text' => 'Terima kasih telah mempercayai ZRINTTAILOR. Jangan lupa beri ulasan Anda.',
                'icon' => 'check-circle'],
            'cancelled'  => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'icon_bg' => 'bg-red-100', 'icon_color' => 'text-red-500', 'title_color' => 'text-red-800', 'text_color' => 'text-red-600',
                'title' => 'Pesanan Dibatalkan', 'text' => 'Pesanan ini telah dibatalkan. Hubungi admin jika ada pertanyaan.',
                'icon' => 'x-circle'],
            default      => null,
        };

        // Estimasi selesai: cari tanggal status 'confirmed', tambah estimated_days
        $confirmedStatus = $order->statuses->where('status', 'confirmed')->first();
        $estimatedDone = $confirmedStatus
            ? $confirmedStatus->created_at->addDays($order->service->estimated_days ?? 7)
            : null;
    @endphp

    @if($bannerConfig)
    <div class="rounded-2xl border {{ $bannerConfig['border'] }} {{ $bannerConfig['bg'] }} p-4 mb-6 flex items-start gap-3">
        <div class="w-10 h-10 rounded-xl {{ $bannerConfig['icon_bg'] }} flex items-center justify-center flex-shrink-0">
            @if($bannerConfig['icon'] === 'clock')
                <svg class="w-5 h-5 {{ $bannerConfig['icon_color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @elseif($bannerConfig['icon'] === 'payment')
                <svg class="w-5 h-5 {{ $bannerConfig['icon_color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            @elseif($bannerConfig['icon'] === 'scissors')
                <svg class="w-5 h-5 {{ $bannerConfig['icon_color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
            @elseif($bannerConfig['icon'] === 'truck')
                <svg class="w-5 h-5 {{ $bannerConfig['icon_color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h4l2.68 13.39a2 2 0 001.95 1.61h9.72a2 2 0 001.95-1.61L23 6H6"/></svg>
            @elseif($bannerConfig['icon'] === 'x-circle')
                <svg class="w-5 h-5 {{ $bannerConfig['icon_color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @else
                <svg class="w-5 h-5 {{ $bannerConfig['icon_color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-sm {{ $bannerConfig['title_color'] }}">{{ $bannerConfig['title'] }}</p>
            <p class="text-xs mt-0.5 {{ $bannerConfig['text_color'] }}">{{ $bannerConfig['text'] }}</p>
            @if($estimatedDone && in_array($order->status, ['confirmed', 'processing']))
                <p class="text-xs mt-1.5 font-semibold {{ $bannerConfig['title_color'] }}">
                    Estimasi selesai: {{ $estimatedDone->format('d F Y') }}
                    <span class="font-normal {{ $bannerConfig['text_color'] }}">
                        ({{ $estimatedDone->isFuture() ? 'sekitar ' . now()->diffInDays($estimatedDone) . ' hari lagi' : 'sudah melewati estimasi' }})
                    </span>
                </p>
            @endif
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Kolom Kiri --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Info Utama --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="font-bold text-gray-900 mb-4">Informasi Pesanan</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="bg-gray-50 rounded-xl p-3">
                        <dt class="text-xs text-gray-400 mb-0.5">Kode Pesanan</dt>
                        <dd class="font-mono font-bold text-gray-900">{{ $order->order_code }}</dd>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <dt class="text-xs text-gray-400 mb-0.5">Tanggal Pesanan</dt>
                        <dd class="font-medium text-gray-900">{{ $order->created_at->format('d F Y') }}</dd>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <dt class="text-xs text-gray-400 mb-0.5">Jenis Layanan</dt>
                        <dd class="font-medium text-gray-900">{{ $order->service->name }}</dd>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <dt class="text-xs text-gray-400 mb-0.5">Total Harga</dt>
                        <dd class="font-bold text-gray-900">
                            {{ $order->total_price ? 'Rp ' . number_format($order->total_price, 0, ',', '.') : 'Menunggu konfirmasi' }}
                        </dd>
                    </div>
                    @if($order->catalog)
                    <div class="bg-gray-50 rounded-xl p-3 sm:col-span-2">
                        <dt class="text-xs text-gray-400 mb-0.5">Katalog Dipilih</dt>
                        <dd class="font-medium text-gray-900">{{ $order->catalog->name }}</dd>
                    </div>
                    @endif
                </dl>

                @if($order->shipment)
                <div class="mt-5 pt-5 border-t border-gray-50">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-blue-50 border border-blue-100 rounded-xl p-4">
                        <div>
                            <p class="text-xs text-blue-600 font-medium mb-1">Resi Pengiriman · <span class="uppercase font-bold">{{ $order->shipment->expedition }}</span></p>
                            <p class="font-mono font-bold text-gray-900 text-lg">{{ $order->shipment->tracking_number }}</p>
                        </div>
                        <a href="{{ route('user.tracking.show', $order) }}"
                           class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-xl font-semibold text-sm hover:bg-blue-700 transition-colors shadow-sm flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                            Lacak Pesanan
                        </a>
                    </div>
                </div>
                @endif
            </div>

            {{-- Konfirmasi Terima Barang (saat status shipped) --}}
            @if($order->status === 'shipped')
            <div class="bg-white rounded-2xl border border-orange-200 shadow-sm p-6" x-data="{ showReport: false }">
                <h2 class="font-bold text-gray-900 mb-1">Konfirmasi Penerimaan Barang</h2>
                <p class="text-sm text-gray-500 mb-5">Sudah menerima pesanan? Konfirmasi di sini agar pesanan dinyatakan selesai.</p>

                <div class="flex flex-col sm:flex-row gap-3">
                    {{-- Tombol sudah terima --}}
                    <form action="{{ route('user.orders.confirm', $order) }}" method="POST" class="flex-1"
                          onsubmit="return confirm('Konfirmasi bahwa Anda sudah menerima pesanan ini?')">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center justify-center gap-2 px-5 py-3 bg-green-600 text-white rounded-xl font-semibold text-sm hover:bg-green-700 transition-colors shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Saya Sudah Menerima Barang
                        </button>
                    </form>

                    {{-- Tombol lapor masalah --}}
                    <button type="button" @click="showReport = !showReport"
                            class="flex-1 flex items-center justify-center gap-2 px-5 py-3 border border-red-300 text-red-600 bg-red-50 rounded-xl font-semibold text-sm hover:bg-red-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Barang Tidak Diterima
                    </button>
                </div>

                {{-- Form laporan masalah --}}
                <div x-show="showReport" x-collapse class="mt-4 pt-4 border-t border-red-100">
                    <form action="{{ route('user.orders.report', $order) }}" method="POST" class="space-y-3">
                        @csrf
                        <label class="block text-sm font-semibold text-gray-700">Ceritakan masalahnya:</label>
                        <textarea name="issue_note" rows="3" required
                                  class="w-full rounded-xl border-red-200 text-sm focus:border-red-400 focus:ring-red-400"
                                  placeholder="Contoh: Paket belum sampai setelah 7 hari, tidak ada info kurir, dll."></textarea>
                        <button type="submit"
                                class="px-5 py-2.5 bg-red-600 text-white rounded-xl font-semibold text-sm hover:bg-red-700 transition-colors">
                            Kirim Laporan ke Admin
                        </button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Detail Kebutuhan --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="font-bold text-gray-900 mb-4">Detail Kebutuhan</h2>
                <p class="text-gray-700 whitespace-pre-line text-sm leading-relaxed">{{ $order->description }}</p>

                @if($order->reference_image)
                <div class="mt-4 pt-4 border-t border-gray-50">
                    <p class="text-xs text-gray-400 font-medium mb-2">Foto Referensi</p>
                    <img src="{{ Storage::url($order->reference_image) }}" alt="Referensi"
                         class="max-w-xs rounded-xl border border-gray-200 shadow-sm">
                </div>
                @endif

                <div class="mt-4 pt-4 border-t border-gray-50">
                    <p class="text-xs text-gray-400 font-medium mb-1">Alamat Pengiriman</p>
                    <p class="text-sm text-gray-700">{{ $order->address }}</p>
                </div>

                @if($order->notes)
                <div class="mt-4 pt-4 border-t border-gray-50">
                    <p class="text-xs text-gray-400 font-medium mb-1">Catatan dari Admin</p>
                    <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3">
                        <p class="text-sm text-blue-800">{{ $order->notes }}</p>
                    </div>
                </div>
                @endif
            </div>

            {{-- Pembayaran --}}
            @if($order->total_price)
                @php $latestPayment = $order->latestPayment; @endphp

                @if(!$latestPayment || $latestPayment->status === 'rejected')
                <div class="bg-white rounded-2xl border border-amber-200 shadow-sm p-6">
                    @if($latestPayment && $latestPayment->status === 'rejected')
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
                            <p class="text-sm text-red-700 font-semibold mb-0.5">Pembayaran sebelumnya ditolak</p>
                            <p class="text-xs text-red-600">Alasan: {{ $latestPayment->reject_reason }}</p>
                        </div>
                    @endif
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                </div>
                                <p class="font-bold text-gray-900">Pembayaran Diperlukan</p>
                            </div>
                            <p class="text-sm text-gray-500">Total: <strong class="text-gray-900">Rp {{ number_format($order->total_price, 0, ',', '.') }}</strong></p>
                        </div>
                        <a href="{{ route('user.payment.create', $order) }}"
                           class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-xl font-semibold text-sm hover:bg-blue-700 transition-colors shadow-sm flex-shrink-0">
                            Bayar Sekarang
                        </a>
                    </div>
                </div>
                @elseif($latestPayment->status === 'pending')
                <div class="bg-white rounded-2xl border border-blue-200 shadow-sm p-6 text-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="font-bold text-blue-800 mb-1">Menunggu Verifikasi Pembayaran</p>
                    <p class="text-sm text-blue-600">Rp {{ number_format($latestPayment->amount, 0, ',', '.') }} ({{ $latestPayment->payment_type === 'full' ? 'Lunas' : 'DP' }})</p>
                    <p class="text-xs text-blue-400 mt-1">Diupload {{ $latestPayment->created_at->diffForHumans() }}</p>
                </div>
                @elseif($latestPayment->status === 'verified')
                <div class="bg-white rounded-2xl border border-green-200 shadow-sm p-6 text-center">
                    <div class="w-12 h-12 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="font-bold text-green-800 mb-1">Pembayaran Terverifikasi</p>
                    <p class="text-sm text-green-600">Rp {{ number_format($latestPayment->amount, 0, ',', '.') }} ({{ $latestPayment->payment_type === 'full' ? 'Lunas' : 'DP' }})</p>
                </div>
                @endif
            @elseif($order->status === 'pending')
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 text-center">
                    <p class="text-gray-500 text-sm">Harga pesanan belum ditentukan oleh admin. Silakan tunggu konfirmasi.</p>
                </div>
            @endif

            {{-- Rating (order selesai & belum rating) --}}
            @if($order->status === 'completed')
                @php
                    $hasRated = \App\Models\Testimonial::where('order_id', $order->id)->where('user_id', auth()->id())->exists();
                @endphp
                @if(!$hasRated)
                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 border border-yellow-200 rounded-2xl p-6 text-center">
                    <div class="flex justify-center gap-1 mb-3">
                        @for($i = 0; $i < 5; $i++)
                        <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                    <p class="font-bold text-gray-900 mb-1">Pesanan Selesai!</p>
                    <p class="text-sm text-gray-500 mb-4">Bagaimana pengalaman Anda? Berikan rating untuk membantu kami berkembang.</p>
                    <a href="{{ route('user.testimonials.create', $order) }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-yellow-500 text-white rounded-xl font-semibold text-sm hover:bg-yellow-600 transition-colors shadow-sm">
                        Beri Rating Sekarang
                    </a>
                </div>
                @else
                <div class="bg-green-50 border border-green-200 rounded-2xl p-4 text-center">
                    <p class="text-green-700 font-semibold text-sm">Anda sudah memberikan rating. Terima kasih!</p>
                </div>
                @endif
            @endif
        </div>

        {{-- Kolom Kanan: Riwayat Status --}}
        <div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h2 class="font-bold text-gray-900 mb-5">Riwayat Status</h2>

                @if($order->statuses->isEmpty())
                    <p class="text-sm text-gray-400">Belum ada riwayat.</p>
                @else
                    <div class="relative">
                        <div class="absolute left-3.5 top-2 bottom-2 w-0.5 bg-gray-100"></div>
                        <div class="space-y-5">
                            @foreach($order->statuses as $status)
                            <div class="relative flex items-start">
                                <div class="flex-shrink-0 w-7 h-7 rounded-full {{ $loop->first ? 'bg-blue-600 ring-4 ring-blue-100' : 'bg-gray-200' }} flex items-center justify-center z-10">
                                    @if($loop->first)
                                        <div class="w-2 h-2 rounded-full bg-white"></div>
                                    @else
                                        <div class="w-1.5 h-1.5 rounded-full bg-gray-400"></div>
                                    @endif
                                </div>
                                <div class="ml-4 flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800">{{ $status->status_label }}</p>
                                    @if($status->note)
                                        <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ $status->note }}</p>
                                    @endif
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ $status->created_at->format('d M Y, H:i') }}
                                    </p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
