@extends('layouts.admin')
@section('page-title', 'Pengaturan')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Pengaturan</h1>
        <p class="text-sm text-gray-500 mt-1">Konfigurasi informasi pembayaran</p>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl mb-5 flex items-center gap-2">
        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color:#3b82f6;">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </div>
            <div>
                <p class="font-semibold text-gray-700 text-sm">Pembayaran DANA</p>
                <p class="text-xs text-gray-400">Nomor, nama pemilik, & QR Code</p>
            </div>
        </div>

        <div class="p-6 space-y-8">

            {{-- Form Nomor & Nama --}}
            <form action="{{ route('admin.settings.saveDana') }}" method="POST" class="space-y-4">
                @csrf
                @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                    @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
                </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nomor DANA <span class="text-red-500">*</span></label>
                        <input type="text" name="dana_number" value="{{ old('dana_number', $danaNumber) }}"
                               class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm font-mono"
                               placeholder="08xxxxxxxxxx" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Pemilik <span class="text-red-500">*</span></label>
                        <input type="text" name="dana_name" value="{{ old('dana_name', $danaName) }}"
                               class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm"
                               placeholder="Nama pemilik akun DANA" required>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl font-semibold text-sm text-white transition-colors shadow-sm"
                            style="background-color:#2563eb;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan
                    </button>
                </div>
            </form>

            <hr class="border-gray-100">

            {{-- QR Code Upload --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-3">QR Code DANA</h3>

                @if($qrExists)
                <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-xl flex items-start gap-4">
                    <div class="bg-white rounded-xl p-2 shadow-sm border border-blue-100 flex-shrink-0">
                        <img src="{{ asset('storage/dana/qr_code.png') }}?v={{ filemtime(storage_path('app/public/dana/qr_code.png')) }}"
                             class="w-28 h-28 object-contain rounded-lg" alt="QR DANA aktif">
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-2 h-2 rounded-full bg-green-500"></div>
                            <p class="text-sm font-semibold text-green-700">QR Code Aktif</p>
                        </div>
                        <p class="text-xs text-blue-600 mb-3">QR ini ditampilkan ke pelanggan saat halaman pembayaran.</p>
                        <form action="{{ route('admin.settings.deleteQr') }}" method="POST"
                              onsubmit="return confirm('Hapus QR Code?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium underline">
                                Hapus QR Code
                            </button>
                        </form>
                    </div>
                </div>
                @else
                <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p class="text-xs text-amber-700">QR Code belum diupload — pelanggan hanya bisa transfer manual via nomor.</p>
                </div>
                @endif

                <form action="{{ route('admin.settings.uploadQr') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    @error('qr_image')<p class="text-xs text-red-500">{{ $message }}</p>@enderror

                    <div x-data="{ preview: null, fileName: '' }">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            {{ $qrExists ? 'Ganti QR Code' : 'Upload QR Code DANA' }} <span class="text-red-500">*</span>
                        </label>
                        <div class="border-2 border-dashed border-gray-300 rounded-xl p-5 text-center hover:border-blue-400 hover:bg-blue-50/30 transition-all cursor-pointer"
                             @click="$refs.qrInput.click()"
                             :class="preview ? 'border-green-400 bg-green-50' : ''">
                            <template x-if="preview">
                                <div class="space-y-2">
                                    <img :src="preview" class="w-32 h-32 object-contain mx-auto rounded-xl border border-gray-200 shadow-sm">
                                    <p class="text-sm font-semibold text-green-700" x-text="fileName"></p>
                                    <p class="text-xs text-gray-400">Klik untuk ganti</p>
                                </div>
                            </template>
                            <template x-if="!preview">
                                <div class="space-y-2">
                                    <svg class="w-10 h-10 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    <p class="text-sm text-gray-500 font-medium">Klik untuk pilih file QR</p>
                                    <p class="text-xs text-gray-400">JPG, PNG, WEBP — Maks 2MB</p>
                                </div>
                            </template>
                            <input type="file" x-ref="qrInput" name="qr_image" accept="image/*" class="hidden"
                                   @change="const f=$event.target.files[0]; if(f){fileName=f.name; preview=URL.createObjectURL(f)}">
                        </div>
                        <p class="text-xs text-gray-400 mt-1.5">
                            Cara: Buka app DANA → <strong>Terima Uang</strong> → <strong>QR Code Saya</strong> → Screenshot
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl font-semibold text-sm text-white shadow-sm"
                                style="background-color:#2563eb;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            {{ $qrExists ? 'Ganti QR Code' : 'Upload QR Code' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
