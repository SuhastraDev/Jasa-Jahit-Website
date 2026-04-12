@extends('layouts.user')
@section('page-title', 'Upload Pembayaran')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6">
        <a href="{{ route('user.orders.show', $order) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali ke Detail Pesanan
        </a>
        <h1 class="text-xl font-bold text-gray-900 mt-2">Upload Bukti Pembayaran</h1>
        <p class="text-sm text-gray-500 mt-1 font-mono">{{ $order->order_code }}</p>
    </div>

    {{-- Info Transfer DANA --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-5">
        <h2 class="font-bold text-gray-900 mb-4">Transfer ke DANA</h2>

        {{-- Card Gradien DANA --}}
        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-5 mb-5 text-white">

            {{-- QR DANA (hanya jika admin upload QR resmi) + Info Nomor --}}
            <div class="flex flex-col sm:flex-row items-center gap-5 mb-4">
                @if($danaQrExists)
                {{-- QR resmi dari admin --}}
                <div class="flex-shrink-0 bg-white rounded-2xl p-3 shadow-lg text-center">
                    <img src="{{ asset('storage/dana/qr_code.png') }}?v={{ filemtime(storage_path('app/public/dana/qr_code.png')) }}"
                         class="w-40 h-40 object-contain rounded-lg" alt="QR Code DANA">
                    <p class="text-[10px] text-blue-600 font-semibold mt-1.5">Scan dengan aplikasi DANA</p>
                </div>
                @endif

                {{-- Info Nomor --}}
                <div class="flex-1 text-center sm:text-left">
                    <p class="text-blue-200 text-xs font-medium mb-1">Nomor DANA</p>
                    <p class="text-2xl sm:text-3xl font-bold tracking-widest mb-1">{{ $danaNumber }}</p>
                    <p class="text-blue-200 text-sm">a/n <strong class="text-white">{{ $danaName }}</strong></p>
                    <button onclick="navigator.clipboard.writeText('{{ $danaNumber }}').then(()=>{ this.textContent='✓ Tersalin!'; setTimeout(()=>this.textContent='Salin Nomor',2000) })"
                            class="mt-3 inline-flex items-center gap-1.5 bg-white/20 hover:bg-white/30 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        Salin Nomor
                    </button>
                    @if(!$danaQrExists)
                    <p class="text-blue-200 text-xs mt-2">Transfer manual via nomor di atas atau buka DANA → Kirim Uang</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Total & Kode --}}
        <div class="flex items-center justify-between bg-gray-50 rounded-xl p-4 mb-5">
            <div>
                <p class="text-xs text-gray-400 mb-0.5">Total yang harus dibayar</p>
                <p class="text-2xl font-bold text-gray-900">Rp {{ number_format($order->total_price, 0, ',', '.') }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400 mb-0.5">Kode Pesanan</p>
                <p class="font-mono font-bold text-gray-700">{{ $order->order_code }}</p>
            </div>
        </div>

        {{-- Petunjuk --}}
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                </div>
                <div class="text-sm text-amber-800">
                    <p class="font-semibold mb-2">Petunjuk Pembayaran:</p>
                    <ol class="list-decimal list-inside space-y-1 text-amber-700 text-xs">
                        <li>Buka aplikasi DANA di HP Anda</li>
                        <li>Tap <strong>Kirim Uang</strong> → masukkan nomor atau scan QR</li>
                        <li>Masukkan nominal sesuai total pesanan</li>
                        <li>Screenshot bukti transfer</li>
                        <li>Upload bukti di form di bawah ini</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Form Upload Bukti --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <h2 class="font-bold text-gray-900 mb-5">Form Upload Bukti</h2>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-5">
                <ul class="list-disc list-inside text-sm space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('user.payment.store', $order) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Jumlah Dibayar (Rp) <span class="text-red-500">*</span></label>
                <input type="number" name="amount" value="{{ old('amount', $order->total_price) }}"
                       class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm"
                       placeholder="Nominal transfer" required>
                <p class="text-xs text-gray-400 mt-1">Bayar penuh sesuai total pesanan: <strong>Rp {{ number_format($order->total_price, 0, ',', '.') }}</strong></p>
            </div>

            {{-- Upload file bukti dengan styling warna jelas --}}
            <div x-data="{ preview: null, fileName: '' }">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Foto Bukti Transfer <span class="text-red-500">*</span></label>
                <div class="relative border-2 border-dashed border-blue-300 bg-blue-50 rounded-2xl p-6 text-center hover:border-blue-500 hover:bg-blue-100/50 transition-all cursor-pointer"
                     @click="$refs.proofInput.click()"
                     :class="preview ? 'border-green-400 bg-green-50' : 'border-blue-300 bg-blue-50'">

                    <template x-if="preview">
                        <div class="space-y-3">
                            <img :src="preview" class="max-h-48 mx-auto rounded-xl shadow-sm border border-gray-200">
                            <div class="flex items-center justify-center gap-2 text-green-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span class="text-sm font-semibold" x-text="fileName"></span>
                            </div>
                            <p class="text-xs text-blue-600 font-medium">Klik untuk ganti foto</p>
                        </div>
                    </template>

                    <template x-if="!preview">
                        <div class="space-y-3">
                            <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto">
                                <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-blue-700">Klik untuk upload screenshot transfer</p>
                                <p class="text-xs text-blue-500 mt-0.5">JPG, PNG, WEBP — Maks 2MB</p>
                            </div>
                        </div>
                    </template>

                    <input type="file" x-ref="proofInput" name="proof_image" accept="image/*" required class="hidden"
                           @change="
                               const f = $event.target.files[0];
                               if(f){ fileName = f.name; preview = URL.createObjectURL(f); }
                           ">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-50">
                <a href="{{ route('user.orders.show', $order) }}" class="px-4 py-2.5 text-sm text-gray-500 hover:text-gray-700 font-medium">Batal</a>
                <button type="submit"
                    class="px-6 py-2.5 bg-blue-600 text-white rounded-xl font-semibold text-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors shadow-sm">
                    Upload Bukti Pembayaran
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
