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

        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-5 mb-5 text-white">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-blue-200 text-xs font-medium mb-0.5">Nomor DANA</p>
                    <p class="text-2xl font-bold tracking-wider">{{ $danaNumber }}</p>
                </div>
            </div>
            <p class="text-blue-200 text-sm">a/n <strong class="text-white">{{ $danaName }}</strong></p>
        </div>

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

        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                </div>
                <div class="text-sm text-amber-800">
                    <p class="font-semibold mb-2">Petunjuk Pembayaran:</p>
                    <ol class="list-decimal list-inside space-y-1 text-amber-700 text-xs">
                        <li>Buka aplikasi DANA di HP Anda</li>
                        <li>Transfer ke nomor di atas sesuai nominal</li>
                        <li>Screenshot bukti transfer</li>
                        <li>Upload bukti di form di bawah ini</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Form Upload --}}
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

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Jumlah Dibayar (Rp) <span class="text-red-500">*</span></label>
                    <input type="number" name="amount" value="{{ old('amount', $order->total_price) }}"
                           class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm"
                           placeholder="Nominal transfer" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Tipe Pembayaran <span class="text-red-500">*</span></label>
                    <select name="payment_type" class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm" required>
                        <option value="full" selected>Bayar Lunas</option>
                        <option value="dp">DP (Uang Muka)</option>
                    </select>
                </div>
            </div>

            <div x-data="{ preview: null }" class="relative">
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Foto Bukti Transfer <span class="text-red-500">*</span></label>
                <input type="file" name="proof_image" accept="image/*" required
                    @change="preview = URL.createObjectURL($event.target.files[0])"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                <p class="text-xs text-gray-400 mt-1">Format JPG, PNG, WEBP. Maks 2MB.</p>
                <div x-show="preview" class="mt-3">
                    <img :src="preview" class="max-h-40 rounded-xl border border-gray-200 shadow-sm" alt="Preview bukti">
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
