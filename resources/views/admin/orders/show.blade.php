@extends('layouts.admin')
@section('page-title', 'Detail Pesanan')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.orders.index') }}"
           class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-100 transition-colors flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail Pesanan</h1>
            <p class="font-mono text-blue-600 text-sm font-semibold">{{ $order->order_code }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Kolom Kiri: Info Pesanan --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Info Pelanggan --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h4 class="text-md font-bold text-gray-800 mb-4">Informasi Pelanggan</h4>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Nama</dt>
                        <dd class="font-semibold text-gray-900">{{ $order->user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Email</dt>
                        <dd class="text-gray-900">{{ $order->user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">No. HP</dt>
                        <dd class="text-gray-900">{{ $order->user->phone ?? '-' }}</dd>
                    </div>
                    @if($order->address)
                    <div>
                        <dt class="text-gray-500">Alamat Pengiriman</dt>
                        <dd class="text-gray-900">{{ $order->address }}</dd>
                    </div>
                    @elseif($serviceType === 'design')
                    <div>
                        <dt class="text-gray-500">Pengiriman</dt>
                        <dd class="text-purple-600 font-medium text-sm">File digital — tidak ada pengiriman fisik</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-gray-500">No. HP untuk Kurir</dt>
                        <dd class="font-semibold text-gray-900 flex items-center gap-2">
                            {{ $order->recipient_phone ?? $order->user->phone ?? '-' }}
                            @if($order->recipient_phone ?? $order->user->phone)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->recipient_phone ?? $order->user->phone) }}"
                               target="_blank"
                               class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 text-green-700 text-xs font-semibold rounded-lg hover:bg-green-200 transition-colors">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                WA
                            </a>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Info Pesanan --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-md font-bold text-gray-800">Detail Pesanan</h4>
                    @php
                        $colors = [
                            'yellow' => 'bg-yellow-100 text-yellow-800',
                            'blue' => 'bg-blue-100 text-blue-800',
                            'indigo' => 'bg-indigo-100 text-indigo-800',
                            'purple' => 'bg-purple-100 text-purple-800',
                            'orange' => 'bg-orange-100 text-orange-800',
                            'green' => 'bg-green-100 text-green-800',
                            'red' => 'bg-red-100 text-red-800',
                            'gray' => 'bg-gray-100 text-gray-800',
                        ];
                    @endphp
                    <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $colors[$order->status_color] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ $order->status_label }}
                    </span>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Kode Pesanan</dt>
                        <dd class="font-mono font-bold text-gray-900">{{ $order->order_code }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Tanggal Pesanan</dt>
                        <dd class="text-gray-900">{{ $order->created_at->format('d F Y, H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Jenis Layanan</dt>
                        <dd class="flex items-center gap-2 flex-wrap">
                            <span class="text-gray-900 font-medium">{{ $order->service->name }}</span>
                            @php
                                $typeBadge = match($serviceType) {
                                    'design' => ['bg-purple-100 text-purple-700', 'Desain Digital'],
                                    'permak' => ['bg-orange-100 text-orange-700', 'Permak'],
                                    default  => ['bg-blue-100 text-blue-700', 'Jahit Custom'],
                                };
                            @endphp
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $typeBadge[0] }}">{{ $typeBadge[1] }}</span>
                        </dd>
                    </div>
                    @if($order->catalog)
                    <div>
                        <dt class="text-gray-500">Katalog Dipilih</dt>
                        <dd class="text-gray-900">{{ $order->catalog->name }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-gray-500">Total Harga</dt>
                        <dd class="text-gray-900 font-semibold">
                            {{ $order->total_price ? 'Rp ' . number_format($order->total_price, 0, ',', '.') : 'Belum ditentukan' }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-4 pt-4 border-t">
                    <p class="text-sm text-gray-500 mb-1">Deskripsi Kebutuhan:</p>
                    <p class="text-gray-700 whitespace-pre-line">{{ $order->description }}</p>
                </div>

                @if($order->reference_image)
                <div class="mt-4 pt-4 border-t">
                    <p class="text-sm text-gray-500 mb-2">Foto Referensi:</p>
                    <img src="{{ Storage::url($order->reference_image) }}" alt="Referensi"
                         class="max-w-sm rounded-lg border border-gray-200 shadow-sm">
                </div>
                @endif

                @if($order->measurement)
                <div class="mt-4 pt-4 border-t">
                    <p class="text-sm text-gray-500 mb-2">Ukuran Badan Pelanggan:</p>
                    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-sm">
                        <div><dt class="text-gray-400">Dada</dt><dd class="font-medium">{{ $order->measurement->chest }} cm</dd></div>
                        <div><dt class="text-gray-400">Pinggang</dt><dd class="font-medium">{{ $order->measurement->waist }} cm</dd></div>
                        <div><dt class="text-gray-400">Pinggul</dt><dd class="font-medium">{{ $order->measurement->hips }} cm</dd></div>
                        <div><dt class="text-gray-400">Bahu</dt><dd class="font-medium">{{ $order->measurement->shoulder_width }} cm</dd></div>
                        <div><dt class="text-gray-400">Tinggi</dt><dd class="font-medium">{{ $order->measurement->height }} cm</dd></div>
                    </dl>
                </div>
                @endif
            </div>

            {{-- Status Pembayaran --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h4 class="text-md font-bold text-gray-800 mb-4">Status Pembayaran</h4>
                @if(!$order->total_price)
                    <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl border border-gray-200">
                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm text-gray-500">Harga belum ditentukan. Konfirmasi pesanan dengan mengisi total harga terlebih dahulu.</p>
                    </div>
                @elseif($order->payments->isEmpty())
                    <div class="flex items-center gap-3 p-4 bg-red-50 rounded-xl border border-red-200">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <p class="text-sm font-semibold text-red-700">Pelanggan belum melakukan pembayaran</p>
                            <p class="text-xs text-red-500 mt-0.5">Total tagihan: <strong>Rp {{ number_format($order->total_price, 0, ',', '.') }}</strong></p>
                        </div>
                    </div>
                @else
                    @foreach($order->payments as $payment)
                    <div class="flex items-start gap-4 p-4 rounded-xl border mb-3 {{ $payment->status === 'verified' ? 'bg-green-50 border-green-200' : ($payment->status === 'pending' ? 'bg-orange-50 border-orange-200' : 'bg-red-50 border-red-200') }}">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm font-bold {{ $payment->status === 'verified' ? 'text-green-800' : ($payment->status === 'pending' ? 'text-orange-800' : 'text-red-800') }}">
                                    Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                </span>
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $payment->status === 'verified' ? 'bg-green-200 text-green-800' : ($payment->status === 'pending' ? 'bg-orange-200 text-orange-800' : 'bg-red-200 text-red-800') }}">
                                    {{ $payment->status_label }}
                                </span>
                                <span class="text-xs text-gray-500">{{ $payment->payment_type === 'full' ? 'Lunas' : 'DP' }}</span>
                            </div>
                            <p class="text-xs text-gray-500">Diupload {{ $payment->created_at->format('d M Y, H:i') }}</p>
                            @if($payment->reject_reason)
                                <p class="text-xs text-red-600 mt-1">Alasan ditolak: {{ $payment->reject_reason }}</p>
                            @endif
                        </div>
                        @if($payment->proof_image)
                        <a href="{{ Storage::url($payment->proof_image) }}" target="_blank"
                           class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden border border-gray-200 hover:opacity-80 transition-opacity">
                            <img src="{{ Storage::url($payment->proof_image) }}" alt="Bukti" class="w-full h-full object-cover">
                        </a>
                        @endif
                        @if($payment->status === 'pending')
                        <div class="flex flex-col gap-1.5 flex-shrink-0">
                            <form action="{{ route('admin.payments.verify', $payment) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="px-3 py-1.5 bg-green-600 text-white text-xs font-bold rounded-lg hover:bg-green-700 w-full">Verifikasi</button>
                            </form>
                            <button type="button" onclick="document.getElementById('reject-form-{{ $payment->id }}').classList.toggle('hidden')"
                                    class="px-3 py-1.5 bg-red-600 text-white text-xs font-bold rounded-lg hover:bg-red-700">Tolak</button>
                        </div>
                        @endif
                    </div>
                    <div id="reject-form-{{ $payment->id }}" class="hidden mb-3">
                        <form action="{{ route('admin.payments.reject', $payment) }}" method="POST" class="bg-red-50 border border-red-200 rounded-xl p-3 space-y-2">
                            @csrf @method('PATCH')
                            <input type="text" name="reject_reason" placeholder="Alasan penolakan..." required
                                   class="w-full rounded-lg border-red-300 text-sm focus:border-red-400 focus:ring-red-400">
                            <button type="submit" class="px-4 py-1.5 bg-red-600 text-white text-xs font-bold rounded-lg hover:bg-red-700">Konfirmasi Tolak</button>
                        </form>
                    </div>
                    @endforeach
                @endif
            </div>

            {{-- Aksi Pesanan (Guided Status) --}}
            @php
                $nextStatus = null;
                $nextLabel = null;
                $nextColor = null;
                $nextIcon = null;
                $nextDesc = null;
                $requiresPrice = false;

                if ($order->status === 'pending') {
                    $nextStatus = 'confirmed';
                    $nextLabel = 'Konfirmasi Pesanan';
                    $nextColor = 'blue';
                    $nextIcon = 'check-circle';
                    $nextDesc = match($serviceType) {
                        'design'  => 'Konfirmasi pesanan desain. Pelanggan akan membayar, lalu Anda mengerjakan file desainnya.',
                        'permak'  => 'Konfirmasi pesanan permak. Setelah pelanggan bayar, mereka akan mengirim pakaian ke Anda.',
                        default   => 'Konfirmasi pesanan dan tentukan harga. Pelanggan bisa langsung melakukan pembayaran.',
                    };
                    $requiresPrice = true;
                } elseif ($order->status === 'confirmed') {
                    // Permak: tunggu dulu barang dari pembeli
                    if ($serviceType === 'permak') {
                        $nextStatus = 'processing';
                        $nextLabel = 'Mulai Permak';
                        $nextColor = 'indigo';
                        $nextIcon = 'scissors';
                        $nextDesc = 'Tandai bahwa proses permak sudah dimulai (barang sudah Anda terima & dikerjakan).';
                    } else {
                        $nextStatus = 'processing';
                        $nextLabel = 'Mulai Pengerjaan';
                        $nextColor = 'indigo';
                        $nextIcon = 'scissors';
                        $nextDesc = $serviceType === 'design'
                            ? 'Tandai bahwa desain sedang dikerjakan.'
                            : 'Tandai bahwa proses penjahitan sudah dimulai.';
                    }
                } elseif ($order->status === 'item_received') {
                    $nextStatus = 'processing';
                    $nextLabel = 'Mulai Permak';
                    $nextColor = 'indigo';
                    $nextIcon = 'scissors';
                    $nextDesc = 'Barang sudah diterima. Tandai bahwa proses permak sudah dimulai.';
                } elseif ($order->status === 'processing') {
                    if ($serviceType === 'design') {
                        // Tidak ada tombol aksi — form upload di bawah yang sekaligus mengubah status done
                        $nextStatus = null;
                    } else {
                        $nextStatus = 'done';
                        $nextLabel = $serviceType === 'permak' ? 'Permak Selesai' : 'Selesai Dijahit';
                        $nextColor = 'purple';
                        $nextIcon = 'check';
                        $nextDesc = $serviceType === 'permak'
                            ? 'Tandai permak selesai. Selanjutnya kirim balik ke pelanggan.'
                            : 'Pakaian sudah selesai dibuat dan siap untuk dikirim.';
                    }
                } elseif ($order->status === 'done' && $serviceType !== 'design') {
                    // Tidak ada tombol aksi — input resi di panel bawah yang sekaligus mengubah status shipped
                    $nextStatus = null;
                } elseif ($order->status === 'shipped') {
                    $nextStatus = 'completed';
                    $nextLabel = 'Tandai Pesanan Selesai';
                    $nextColor = 'green';
                    $nextIcon = 'check-circle';
                    $nextDesc = 'Konfirmasi bahwa pelanggan sudah menerima pesanan.';
                }

                $canCancel = !in_array($order->status, ['completed', 'cancelled']);
            @endphp

            @if($nextStatus || $canCancel)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" x-data="{ openAction: null }">
                <h4 class="text-md font-bold text-gray-800 mb-1">Aksi Pesanan</h4>
                <p class="text-sm text-gray-400 mb-5">Pilih tindakan yang sesuai dengan kondisi pesanan saat ini.</p>

                {{-- Error validasi --}}
                @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-4">
                    @foreach($errors->all() as $err)
                        <p class="text-sm text-red-600">{{ $err }}</p>
                    @endforeach
                </div>
                @endif

                <div class="space-y-3">
                    {{-- Tombol aksi utama --}}
                    @if($nextStatus)
                    <div>
                        <button type="button"
                            @click="openAction = openAction === 'next' ? null : 'next'"
                            @php
                                $btnColors = [
                                    'blue'   => 'bg-blue-600 hover:bg-blue-700 text-white',
                                    'indigo' => 'bg-indigo-600 hover:bg-indigo-700 text-white',
                                    'purple' => 'bg-purple-600 hover:bg-purple-700 text-white',
                                    'green'  => 'bg-green-600 hover:bg-green-700 text-white',
                                    'orange' => 'bg-orange-500 hover:bg-orange-600 text-white',
                                ];
                            @endphp
                            class="w-full flex items-center justify-between px-5 py-3 rounded-xl font-semibold text-sm transition-colors {{ $btnColors[$nextColor] ?? 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                            <span class="flex items-center gap-2">
                                @if($nextIcon === 'check-circle')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @elseif($nextIcon === 'scissors')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
                                @elseif($nextIcon === 'truck')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h4l2.68 13.39a2 2 0 001.95 1.61h9.72a2 2 0 001.95-1.61L23 6H6"/></svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @endif
                                {{ $nextLabel }}
                            </span>
                            <svg class="w-4 h-4 transition-transform" :class="openAction === 'next' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="openAction === 'next'" x-collapse class="mt-2 bg-gray-50 rounded-xl border border-gray-200 p-4">
                            <p class="text-xs text-gray-500 mb-4">{{ $nextDesc }}</p>
                            <form action="{{ route('admin.orders.updateStatus', $order) }}" method="POST" class="space-y-3">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $nextStatus }}">

                                @if($requiresPrice)
                                <div x-data="rupiahInput({{ old('total_price', $order->total_price) ?: 0 }})">
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Total Harga <span class="text-red-500">*</span></label>
                                    <div class="flex items-center rounded-lg border border-gray-300 overflow-hidden focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 @error('total_price') border-red-400 @enderror">
                                        <span class="px-3 py-2 bg-gray-50 text-sm text-gray-500 font-semibold border-r border-gray-300">Rp</span>
                                        <input type="text" x-model="display" @input="onInput" @focus="onFocus" @blur="onBlur"
                                               class="flex-1 px-3 py-2 text-sm border-0 focus:ring-0 bg-white"
                                               placeholder="0" inputmode="numeric">
                                    </div>
                                    <input type="hidden" name="total_price" :value="raw">
                                    @error('total_price')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                                    <p class="text-xs text-gray-400 mt-1" x-show="raw > 0" x-text="'= Rp ' + formatRupiah(raw)"></p>
                                </div>
                                @else
                                    @if(!$order->total_price)
                                    <div x-data="rupiahInput(0)">
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Total Harga <span class="text-gray-400 font-normal text-xs">(opsional)</span></label>
                                        <div class="flex items-center rounded-lg border border-gray-300 overflow-hidden focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500">
                                            <span class="px-3 py-2 bg-gray-50 text-sm text-gray-500 font-semibold border-r border-gray-300">Rp</span>
                                            <input type="text" x-model="display" @input="onInput" @focus="onFocus" @blur="onBlur"
                                                   class="flex-1 px-3 py-2 text-sm border-0 focus:ring-0 bg-white"
                                                   placeholder="0" inputmode="numeric">
                                        </div>
                                        <input type="hidden" name="total_price" :value="raw">
                                        <p class="text-xs text-gray-400 mt-1" x-show="raw > 0" x-text="'= Rp ' + formatRupiah(raw)"></p>
                                    </div>
                                    @endif
                                @endif

                                @if($serviceType === 'permak' && $requiresPrice)
                                {{-- Permak: Alamat workshop wajib diisi --}}
                                <div class="bg-orange-50 border border-orange-200 rounded-xl p-3 mb-1">
                                    <p class="text-xs text-orange-700 font-semibold flex items-center gap-1.5 mb-1">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Pesanan Permak — Alamat Workshop Wajib Diisi
                                    </p>
                                    <p class="text-xs text-orange-600">Alamat ini akan ditampilkan kepada pelanggan agar mereka tahu kemana harus mengirimkan pakaian.</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Alamat Workshop / Tempat Pengerjaan <span class="text-red-500">*</span></label>
                                    <textarea name="note" rows="3" required
                                              class="w-full rounded-lg border-orange-300 text-sm focus:border-orange-500 focus:ring-orange-500"
                                              placeholder="Contoh: Jl. Mawar No. 12, RT 03/RW 05, Kel. Sukamaju, Kec. Cilandak, Jakarta Selatan 12345 (WA: 0812-xxxx-xxxx)"></textarea>
                                    <p class="text-xs text-gray-400 mt-1">Sertakan nama jalan, RT/RW, kelurahan, kecamatan, kota, dan nomor WA yang bisa dihubungi.</p>
                                </div>
                                @elseif($serviceType === 'design' && $requiresPrice)
                                {{-- Design: catatan untuk pelanggan (opsional) --}}
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Catatan untuk Pelanggan <span class="text-gray-400 font-normal text-xs">(opsional)</span></label>
                                    <textarea name="note" rows="2"
                                              class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                              placeholder="Contoh: Desain akan kami kerjakan dalam 3 hari kerja..."></textarea>
                                </div>
                                @else
                                {{-- Custom / aksi selain konfirmasi awal --}}
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Catatan <span class="text-gray-400 font-normal text-xs">(opsional)</span></label>
                                    <textarea name="note" rows="2"
                                              class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                              placeholder="Pesan untuk pelanggan..."></textarea>
                                </div>
                                @endif

                                <div class="flex justify-end">
                                    <button type="submit"
                                            class="px-5 py-2 bg-{{ $nextColor }}-600 text-white rounded-lg font-semibold text-sm hover:bg-{{ $nextColor }}-700 transition-colors">
                                        Konfirmasi: {{ $nextLabel }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    {{-- Tombol batalkan --}}
                    @if($canCancel && $order->status !== 'cancelled')
                    <div>
                        <button type="button"
                            @click="openAction = openAction === 'cancel' ? null : 'cancel'"
                            class="w-full flex items-center justify-between px-5 py-3 rounded-xl font-semibold text-sm border border-red-200 text-red-600 bg-red-50 hover:bg-red-100 transition-colors">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                Batalkan Pesanan
                            </span>
                            <svg class="w-4 h-4 transition-transform" :class="openAction === 'cancel' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="openAction === 'cancel'" x-collapse class="mt-2 bg-red-50 rounded-xl border border-red-200 p-4">
                            <p class="text-xs text-red-600 mb-4">Pesanan akan dibatalkan dan pelanggan akan diberitahu via WhatsApp.</p>
                            <form action="{{ route('admin.orders.updateStatus', $order) }}" method="POST" class="space-y-3">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="cancelled">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Alasan Pembatalan <span class="text-gray-400 font-normal text-xs">(opsional)</span></label>
                                    <textarea name="note" rows="2"
                                              class="w-full rounded-lg border-red-200 text-sm focus:border-red-400 focus:ring-red-400"
                                              placeholder="Contoh: Bahan tidak tersedia, ukuran tidak cocok..."></textarea>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit"
                                            class="px-5 py-2 bg-red-600 text-white rounded-lg font-semibold text-sm hover:bg-red-700 transition-colors">
                                        Ya, Batalkan Pesanan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    {{-- Status processing design: arahan ke form upload --}}
                    @if($order->status === 'processing' && $serviceType === 'design')
                    <div class="flex items-start gap-3 px-4 py-3.5 bg-purple-50 rounded-xl border border-purple-200">
                        <svg class="w-5 h-5 text-purple-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <div>
                            <p class="text-sm font-semibold text-purple-800">Desain Selesai — Upload File</p>
                            <p class="text-xs text-purple-600 mt-0.5">Upload file desain di panel <strong>Upload File Desain</strong> di bawah. Status otomatis berubah ke <em>Selesai</em> setelah file diunggah.</p>
                        </div>
                    </div>
                    @endif

                    {{-- Status done non-design: arahan ke form resi --}}
                    @if($order->status === 'done' && $serviceType !== 'design')
                    <div class="flex items-start gap-3 px-4 py-3.5 bg-orange-50 rounded-xl border border-orange-200">
                        <svg class="w-5 h-5 text-orange-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h4l2.68 13.39a2 2 0 001.95 1.61h9.72a2 2 0 001.95-1.61L23 6H6"/></svg>
                        <div>
                            <p class="text-sm font-semibold text-orange-800">Siap Dikirim — Isi Nomor Resi</p>
                            <p class="text-xs text-orange-600 mt-0.5">Masukkan ekspedisi dan nomor resi di panel <strong>Input Resi Pengiriman</strong> di bawah. Status otomatis berubah ke <em>Dikirim</em> setelah resi disimpan.</p>
                        </div>
                    </div>
                    @endif

                    {{-- Jika pesanan sudah selesai/dibatalkan --}}
                    @if(in_array($order->status, ['completed', 'cancelled']))
                    <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 rounded-xl border border-gray-200">
                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm text-gray-500">Pesanan ini sudah {{ $order->status === 'completed' ? 'selesai' : 'dibatalkan' }}. Tidak ada aksi lebih lanjut.</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

            {{-- PERMAK: Info kiriman dari pembeli + tombol konfirmasi terima --}}
            @if($serviceType === 'permak' && $order->buyerShipment)
            <div class="bg-gradient-to-br from-cyan-50 to-teal-50 rounded-2xl border-2 border-cyan-200 shadow-sm p-6 mt-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-cyan-500 rounded-xl flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-cyan-900">Kiriman Barang dari Pembeli</h4>
                        <p class="text-xs text-cyan-600">Pembeli sudah mengirim barang untuk dipermak</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-4">
                    <div class="bg-white rounded-xl p-3 border border-cyan-100">
                        <p class="text-xs text-cyan-400 mb-0.5">Ekspedisi</p>
                        <p class="font-bold text-gray-800 uppercase text-sm">{{ $order->buyerShipment->expedition }}</p>
                    </div>
                    <div class="bg-white rounded-xl p-3 border border-cyan-100">
                        <p class="text-xs text-cyan-400 mb-0.5">No. Resi</p>
                        <p class="font-mono font-bold text-gray-800 text-sm">{{ $order->buyerShipment->tracking_number }}</p>
                    </div>
                    <div class="bg-white rounded-xl p-3 border border-cyan-100">
                        <p class="text-xs text-cyan-400 mb-0.5">Tgl Kirim</p>
                        <p class="font-semibold text-gray-800 text-sm">{{ $order->buyerShipment->shipped_at->format('d M Y') }}</p>
                    </div>
                </div>
                @if($order->buyerShipment->proof_image)
                <div class="mb-4">
                    <p class="text-xs text-cyan-600 font-semibold mb-1.5">Bukti Pengiriman:</p>
                    <img src="{{ Storage::url($order->buyerShipment->proof_image) }}" class="max-h-40 rounded-xl border border-cyan-100 shadow-sm" alt="Bukti kirim">
                </div>
                @endif
                @if($order->buyerShipment->notes)
                <p class="text-sm text-cyan-700 bg-white rounded-xl px-3 py-2 border border-cyan-100 mb-4">{{ $order->buyerShipment->notes }}</p>
                @endif
                @if($order->status === 'waiting_item')
                <form action="{{ route('admin.orders.confirmItemReceived', $order) }}" method="POST">
                    @csrf
                    <div class="flex items-center gap-3">
                        <input type="text" name="note" placeholder="Catatan (opsional)" class="flex-1 rounded-xl border-cyan-200 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                        <button type="submit" class="px-5 py-2.5 bg-teal-600 text-white rounded-xl font-semibold hover:bg-teal-700 transition-colors text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Konfirmasi Barang Diterima
                        </button>
                    </div>
                </form>
                @else
                <div class="flex items-center gap-2 text-teal-700 bg-teal-50 rounded-xl px-3 py-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span class="text-sm font-semibold">Barang sudah dikonfirmasi diterima</span>
                </div>
                @endif
            </div>
            @endif

            {{-- DESAIN: Upload file desain --}}
            @if($serviceType === 'design' && in_array($order->status, ['processing', 'done', 'completed']))
            <div class="bg-gradient-to-br from-purple-50 to-violet-50 rounded-2xl border-2 border-purple-200 shadow-sm p-6 mt-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 bg-purple-500 rounded-xl flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-purple-900">Upload File Desain</h4>
                        <p class="text-xs text-purple-600">Upload file hasil desain untuk diunduh pelanggan</p>
                    </div>
                </div>
                @if($order->design_file)
                <div class="mb-4 p-3 bg-white rounded-xl border border-purple-100 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="text-sm text-gray-700 font-medium">File desain sudah diupload</span>
                    </div>
                    <a href="{{ Storage::url($order->design_file) }}" target="_blank" class="text-xs text-purple-600 hover:text-purple-800 font-semibold underline">Lihat File</a>
                </div>
                @if($order->design_notes)
                <p class="text-sm text-purple-700 bg-white rounded-xl px-3 py-2 border border-purple-100 mb-4">{{ $order->design_notes }}</p>
                @endif
                @endif
                @if(in_array($order->status, ['processing', 'done']))
                <form action="{{ route('admin.orders.uploadDesign', $order) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-purple-800 mb-1.5">{{ $order->design_file ? 'Ganti File Desain' : 'Upload File Desain' }} <span class="text-red-500">*</span></label>
                        <input type="file" name="design_file" accept=".jpg,.jpeg,.png,.webp,.pdf,.zip,.rar" required
                               class="w-full text-sm border border-purple-200 rounded-xl px-3 py-2 bg-white focus:border-purple-500 focus:ring-purple-500">
                        <p class="text-xs text-purple-500 mt-1">JPG, PNG, WEBP, PDF, ZIP, RAR — Maks 20MB</p>
                        @error('design_file')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-purple-800 mb-1.5">Catatan untuk Pelanggan</label>
                        <textarea name="design_notes" rows="2" class="w-full rounded-xl border-purple-200 text-sm focus:border-purple-500 focus:ring-purple-500" placeholder="Contoh: File desain dalam format PDF, resolusi 300dpi...">{{ old('design_notes', $order->design_notes) }}</textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-purple-600 text-white rounded-xl font-semibold hover:bg-purple-700 transition-colors text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            {{ $order->design_file ? 'Update File Desain' : 'Upload & Selesaikan' }}
                        </button>
                    </div>
                </form>
                @endif
            </div>
            @endif

            {{-- Form Input Resi Pengiriman (custom & permak saat status done atau shipped) --}}
            @if($serviceType !== 'design' && in_array($order->status, ['done', 'shipped']))
            <div class="bg-gradient-to-br from-orange-50 to-amber-50 rounded-2xl border-2 border-orange-200 shadow-sm p-6 mt-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 bg-orange-500 rounded-xl flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                        <h4 class="text-md font-bold text-orange-900">Input Resi Pengiriman</h4>
                        <p class="text-xs text-orange-600">Pakaian selesai dijahit — masukkan nomor resi ekspedisi.</p>
                    </div>
                </div>

                @if($order->shipment)
                    <div class="mb-5 p-4 border border-blue-200 bg-blue-50 rounded-xl">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <p class="text-sm text-blue-800 font-semibold">Resi sudah diinput</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-sm text-blue-700">
                            <div><span class="text-xs text-blue-400 block">Ekspedisi</span><span class="font-bold uppercase">{{ $order->shipment->expedition }}</span></div>
                            <div><span class="text-xs text-blue-400 block">Tgl Kirim</span>{{ $order->shipment->shipped_at->format('d M Y, H:i') }}</div>
                            <div><span class="text-xs text-blue-400 block">No. Resi</span><span class="font-mono bg-white px-2 py-0.5 rounded border border-blue-100 text-xs">{{ $order->shipment->tracking_number }}</span></div>
                        </div>
                    </div>
                @endif

                <form action="{{ route('admin.shipments.store', $order) }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-orange-800 mb-1.5">Pilih Ekspedisi</label>
                            <select name="expedition" class="w-full rounded-xl border-orange-200 bg-white text-sm focus:border-orange-500 focus:ring-orange-500">
                                <option value="jne" {{ optional($order->shipment)->expedition === 'jne' ? 'selected' : '' }}>JNE</option>
                                <option value="pos" {{ optional($order->shipment)->expedition === 'pos' ? 'selected' : '' }}>POS Indonesia</option>
                                <option value="tiki" {{ optional($order->shipment)->expedition === 'tiki' ? 'selected' : '' }}>TIKI</option>
                                <option value="sicepat" {{ optional($order->shipment)->expedition === 'sicepat' ? 'selected' : '' }}>SiCepat</option>
                                <option value="anteraja" {{ optional($order->shipment)->expedition === 'anteraja' ? 'selected' : '' }}>AnterAja</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-orange-800 mb-1.5">Nomor Resi</label>
                            <input type="text" name="tracking_number" value="{{ optional($order->shipment)->tracking_number }}" required
                                   class="w-full rounded-xl border-orange-200 bg-white text-sm focus:border-orange-500 focus:ring-orange-500 font-mono"
                                   placeholder="Contoh: 1234567890">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-6 py-2.5 bg-orange-500 text-white rounded-xl font-semibold hover:bg-orange-600 transition-colors shadow-sm">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                {{ $order->shipment ? 'Update Resi' : 'Simpan Resi & Kirim' }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>

        {{-- Kolom Kanan: Riwayat Status + Panduan Alur --}}
        <div class="lg:col-span-1 space-y-5">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-8">
                <h4 class="text-md font-bold text-gray-800 mb-4">Riwayat Status</h4>

                @if($order->statuses->isEmpty())
                    <p class="text-sm text-gray-400">Belum ada riwayat.</p>
                @else
                    <div class="relative">
                        <div class="absolute left-3.5 top-2 bottom-2 w-0.5 bg-gray-200"></div>
                        <div class="space-y-6">
                            @foreach($order->statuses as $status)
                            <div class="relative flex items-start">
                                <div class="flex-shrink-0 w-7 h-7 rounded-full {{ $loop->first ? 'bg-blue-500' : 'bg-gray-300' }} flex items-center justify-center z-10">
                                    <div class="w-2.5 h-2.5 rounded-full bg-white"></div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-semibold text-gray-800">{{ $status->status_label }}</p>
                                    @if($status->note)
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $status->note }}</p>
                                    @endif
                                    <p class="text-xs text-gray-400 mt-1">
                                        {{ $status->created_at->format('d M Y, H:i') }}
                                        @if($status->changedBy)
                                            · oleh {{ $status->changedBy->name }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Panduan Alur Permak --}}
            @if($serviceType === 'permak')
            @php
                $permakSteps = [
                    ['status' => 'pending',        'label' => 'Pesanan Masuk',           'desc' => 'Admin isi alamat workshop & konfirmasi'],
                    ['status' => 'confirmed',       'label' => 'Dikonfirmasi',            'desc' => 'Pelanggan bayar & kirim barang'],
                    ['status' => 'waiting_item',    'label' => 'Menunggu Barang',         'desc' => 'Barang dalam perjalanan ke workshop'],
                    ['status' => 'item_received',   'label' => 'Barang Diterima',         'desc' => 'Konfirmasi terima, mulai permak'],
                    ['status' => 'processing',      'label' => 'Sedang Dipermak',         'desc' => 'Proses pengerjaan berlangsung'],
                    ['status' => 'done',            'label' => 'Permak Selesai',          'desc' => 'Isi resi pengiriman → status otomatis Dikirim'],
                    ['status' => 'shipped',         'label' => 'Dikirim ke Pelanggan',    'desc' => 'Pelanggan konfirmasi terima'],
                    ['status' => 'completed',       'label' => 'Selesai',                 'desc' => 'Pesanan tuntas'],
                ];
                $statusOrder = ['pending','confirmed','waiting_item','item_received','processing','done','shipped','completed'];
                $currentIdx  = array_search($order->status, $statusOrder);
            @endphp
            <div class="bg-white rounded-xl shadow-sm border border-orange-200 p-5">
                <h4 class="text-sm font-bold text-orange-800 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Panduan Alur Permak
                </h4>
                <div class="space-y-2">
                    @foreach($permakSteps as $i => $step)
                    @php
                        $stepIdx  = array_search($step['status'], $statusOrder);
                        $isDone   = $currentIdx !== false && $stepIdx < $currentIdx;
                        $isCurrent= $currentIdx !== false && $stepIdx === $currentIdx;
                    @endphp
                    <div class="flex items-start gap-2.5">
                        <div class="flex-shrink-0 mt-0.5">
                            @if($isDone)
                                <div class="w-5 h-5 rounded-full bg-orange-500 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            @elseif($isCurrent)
                                <div class="w-5 h-5 rounded-full bg-orange-500 ring-4 ring-orange-100 flex items-center justify-center">
                                    <div class="w-2 h-2 rounded-full bg-white"></div>
                                </div>
                            @else
                                <div class="w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center">
                                    <div class="w-2 h-2 rounded-full bg-gray-400"></div>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold {{ $isCurrent ? 'text-orange-700' : ($isDone ? 'text-gray-600' : 'text-gray-400') }}">{{ $step['label'] }}</p>
                            <p class="text-xs {{ $isCurrent ? 'text-orange-500' : 'text-gray-400' }}">{{ $step['desc'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Panduan Alur Desain --}}
            @if($serviceType === 'design')
            @php
                $designSteps = [
                    ['status' => 'pending',    'label' => 'Pesanan Masuk',          'desc' => 'Admin konfirmasi & tentukan harga'],
                    ['status' => 'confirmed',  'label' => 'Dikonfirmasi',           'desc' => 'Pelanggan melakukan pembayaran'],
                    ['status' => 'processing', 'label' => 'Desain Dikerjakan',      'desc' => 'Tim desainer sedang mengerjakan'],
                    ['status' => 'done',       'label' => 'Upload File Desain',     'desc' => 'Upload file & pelanggan download'],
                    ['status' => 'completed',  'label' => 'Selesai',                'desc' => 'Pelanggan konfirmasi terima file'],
                ];
                $designStatusOrder = ['pending','confirmed','processing','done','completed'];
                $currentDesignIdx  = array_search($order->status, $designStatusOrder);
            @endphp
            <div class="bg-white rounded-xl shadow-sm border border-purple-200 p-5">
                <h4 class="text-sm font-bold text-purple-800 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Panduan Alur Desain
                </h4>
                <div class="space-y-2">
                    @foreach($designSteps as $i => $step)
                    @php
                        $dStepIdx   = array_search($step['status'], $designStatusOrder);
                        $dIsDone    = $currentDesignIdx !== false && $dStepIdx < $currentDesignIdx;
                        $dIsCurrent = $currentDesignIdx !== false && $dStepIdx === $currentDesignIdx;
                    @endphp
                    <div class="flex items-start gap-2.5">
                        <div class="flex-shrink-0 mt-0.5">
                            @if($dIsDone)
                                <div class="w-5 h-5 rounded-full bg-purple-500 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            @elseif($dIsCurrent)
                                <div class="w-5 h-5 rounded-full bg-purple-500 ring-4 ring-purple-100 flex items-center justify-center">
                                    <div class="w-2 h-2 rounded-full bg-white"></div>
                                </div>
                            @else
                                <div class="w-5 h-5 rounded-full bg-gray-200 flex items-center justify-center">
                                    <div class="w-2 h-2 rounded-full bg-gray-400"></div>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold {{ $dIsCurrent ? 'text-purple-700' : ($dIsDone ? 'text-gray-600' : 'text-gray-400') }}">{{ $step['label'] }}</p>
                            <p class="text-xs {{ $dIsCurrent ? 'text-purple-500' : 'text-gray-400' }}">{{ $step['desc'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

    </div>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('rupiahInput', (initialValue) => ({
        raw: initialValue || 0,
        display: initialValue ? new Intl.NumberFormat('id-ID').format(initialValue) : '',
        formatRupiah(val) {
            return new Intl.NumberFormat('id-ID').format(val);
        },
        onInput(e) {
            const digits = e.target.value.replace(/\D/g, '');
            this.raw = parseInt(digits) || 0;
            this.display = digits ? new Intl.NumberFormat('id-ID').format(this.raw) : '';
        },
        onFocus() {
            if (this.raw === 0) this.display = '';
        },
        onBlur() {
            this.display = this.raw ? new Intl.NumberFormat('id-ID').format(this.raw) : '';
        }
    }));
});
</script>
@endpush
