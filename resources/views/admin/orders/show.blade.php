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
                    <div>
                        <dt class="text-gray-500">Alamat Pengiriman</dt>
                        <dd class="text-gray-900">{{ $order->address }}</dd>
                    </div>
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
                        <dd class="text-gray-900">{{ $order->service->name }}</dd>
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
                    $nextDesc = 'Pesanan akan dikonfirmasi dan pelanggan bisa melakukan pembayaran.';
                    $requiresPrice = true;
                } elseif ($order->status === 'confirmed') {
                    $nextStatus = 'processing';
                    $nextLabel = 'Mulai Pengerjaan';
                    $nextColor = 'indigo';
                    $nextIcon = 'scissors';
                    $nextDesc = 'Tandai bahwa proses penjahitan sudah dimulai.';
                } elseif ($order->status === 'processing') {
                    $nextStatus = 'done';
                    $nextLabel = 'Selesai Dijahit';
                    $nextColor = 'purple';
                    $nextIcon = 'check';
                    $nextDesc = 'Pakaian sudah selesai dibuat dan siap untuk dikirim.';
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
                                ];
                            @endphp
                            class="w-full flex items-center justify-between px-5 py-3 rounded-xl font-semibold text-sm transition-colors {{ $btnColors[$nextColor] ?? 'bg-blue-600 hover:bg-blue-700 text-white' }}">
                            <span class="flex items-center gap-2">
                                @if($nextIcon === 'check-circle')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @elseif($nextIcon === 'scissors')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
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
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Total Harga <span class="text-red-500">*</span></label>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-gray-500 font-medium">Rp</span>
                                        <input type="number" name="total_price"
                                               value="{{ old('total_price', $order->total_price) }}"
                                               class="flex-1 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 @error('total_price') border-red-400 @enderror"
                                               placeholder="Contoh: 250000" min="0">
                                    </div>
                                    @error('total_price')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                                </div>
                                @else
                                    @if(!$order->total_price)
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Total Harga <span class="text-gray-400 font-normal text-xs">(opsional)</span></label>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm text-gray-500 font-medium">Rp</span>
                                            <input type="number" name="total_price" value="{{ old('total_price') }}"
                                                   class="flex-1 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                                   placeholder="Isi jika belum ditentukan" min="0">
                                        </div>
                                    </div>
                                    @endif
                                @endif

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Catatan <span class="text-gray-400 font-normal text-xs">(opsional)</span></label>
                                    <textarea name="note" rows="2"
                                              class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                                              placeholder="Pesan untuk pelanggan..."></textarea>
                                </div>

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

            {{-- Form Input Resi Pengiriman (hanya saat status done atau shipped) --}}
            @if(in_array($order->status, ['done', 'shipped']))
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-9 h-9 bg-orange-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                        <h4 class="text-md font-bold text-gray-800">Input Resi Pengiriman</h4>
                        <p class="text-xs text-gray-400">Pakaian selesai dijahit — masukkan nomor resi ekspedisi.</p>
                    </div>
                </div>

                @if($order->shipment)
                    <div class="mb-4 p-4 border border-blue-100 bg-blue-50 rounded-lg">
                        <p class="text-sm text-blue-800 font-medium">Resi sudah diinput.</p>
                        <ul class="text-sm text-blue-700 mt-1 space-y-0.5">
                            <li>Ekspedisi: <span class="font-bold uppercase">{{ $order->shipment->expedition }}</span></li>
                            <li>Tgl Kirim: {{ $order->shipment->shipped_at->format('d M Y, H:i') }}</li>
                            <li>No. Resi: <span class="font-mono bg-white px-2 py-0.5 rounded border border-blue-100">{{ $order->shipment->tracking_number }}</span></li>
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.shipments.store', $order) }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Ekspedisi</label>
                            <select name="expedition" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="jne" {{ optional($order->shipment)->expedition === 'jne' ? 'selected' : '' }}>JNE</option>
                                <option value="pos" {{ optional($order->shipment)->expedition === 'pos' ? 'selected' : '' }}>POS Indonesia</option>
                                <option value="tiki" {{ optional($order->shipment)->expedition === 'tiki' ? 'selected' : '' }}>TIKI</option>
                                <option value="sicepat" {{ optional($order->shipment)->expedition === 'sicepat' ? 'selected' : '' }}>SiCepat</option>
                                <option value="anteraja" {{ optional($order->shipment)->expedition === 'anteraja' ? 'selected' : '' }}>AnterAja</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Resi</label>
                            <input type="text" name="tracking_number" value="{{ optional($order->shipment)->tracking_number }}" required
                                   class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 font-mono"
                                   placeholder="Contoh: 1234567890">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-6 py-2.5 bg-orange-600 text-white rounded-lg font-semibold hover:bg-orange-700 transition-colors">
                            {{ $order->shipment ? 'Update Resi' : 'Simpan Resi & Kirim' }}
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>

        {{-- Kolom Kanan: Riwayat Status --}}
        <div class="lg:col-span-1">
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
        </div>

    </div>
</div>
@endsection
