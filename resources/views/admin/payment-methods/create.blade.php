@extends('layouts.admin')
@section('page-title', 'Tambah Metode Pembayaran')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8">

    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.payment-methods.index') }}"
           class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Tambah Metode Pembayaran</h1>
            <p class="text-gray-500 text-sm mt-0.5">Akan muncul sebagai pilihan pembayaran di halaman upload bukti transfer pelanggan</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <form action="{{ route('admin.payment-methods.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Nama Metode <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                    placeholder="Contoh: DANA, GoPay, Bank BCA"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm @error('name') border-red-400 @enderror">
                @error('name')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="account_number" class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Nomor Rekening / E-wallet <span class="text-red-500">*</span>
                </label>
                <input type="text" id="account_number" name="account_number" value="{{ old('account_number') }}" required
                    placeholder="Contoh: 082282208900 atau 1234567890"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm @error('account_number') border-red-400 @enderror">
                @error('account_number')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="account_name" class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Nama Pemilik Rekening <span class="text-red-500">*</span>
                </label>
                <input type="text" id="account_name" name="account_name" value="{{ old('account_name') }}" required
                    placeholder="Contoh: Zikri Wijaya Darmawan"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm @error('account_name') border-red-400 @enderror">
                @error('account_name')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div x-data="{ preview: null, fileName: '' }">
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                    QR Code <span class="text-gray-400 font-normal text-xs">(opsional)</span>
                </label>
                <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-blue-400 transition-colors cursor-pointer"
                     @click="$refs.fileInput.click()"
                     :class="preview ? 'border-blue-300 bg-blue-50/20' : 'bg-gray-50 hover:bg-blue-50/10'">

                    <template x-if="preview">
                        <div class="space-y-3">
                            <img :src="preview" class="max-h-48 mx-auto rounded-xl object-contain shadow-sm">
                            <p class="text-sm text-gray-600 font-medium" x-text="fileName"></p>
                            <p class="text-xs text-blue-600 font-medium">Klik untuk ganti gambar</p>
                        </div>
                    </template>

                    <template x-if="!preview">
                        <div class="space-y-3">
                            <div class="w-12 h-12 bg-gray-200 rounded-xl flex items-center justify-center mx-auto">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700">Klik untuk upload gambar QR Code</p>
                                <p class="text-xs text-gray-400 mt-1">PNG, JPG — Maks. 2MB. Kosongkan jika tidak ada QR (transfer manual saja).</p>
                            </div>
                        </div>
                    </template>

                    <input type="file" x-ref="fileInput" name="qr_image" accept="image/*" class="hidden"
                           @change="
                               const file = $event.target.files[0];
                               if (file) {
                                   fileName = file.name;
                                   const reader = new FileReader();
                                   reader.onload = e => preview = e.target.result;
                                   reader.readAsDataURL(file);
                               }
                           ">
                </div>
                @error('qr_image')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="instructions" class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Petunjuk Pembayaran <span class="text-gray-400 font-normal text-xs">(opsional)</span>
                </label>
                <textarea id="instructions" name="instructions" rows="4"
                    placeholder="Kosongkan untuk memakai petunjuk umum. Isi kalau langkahnya beda, misal khusus transfer bank."
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm resize-none @error('instructions') border-red-400 @enderror">{{ old('instructions') }}</textarea>
                @error('instructions')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <div class="flex gap-3">
                    <label class="flex-1 flex items-center gap-3 border border-gray-200 rounded-xl px-4 py-3 cursor-pointer hover:border-blue-300 hover:bg-blue-50/30 transition-all has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                        <input type="radio" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Aktif</p>
                            <p class="text-xs text-gray-400">Tampil sebagai pilihan pembayaran</p>
                        </div>
                    </label>
                    <label class="flex-1 flex items-center gap-3 border border-gray-200 rounded-xl px-4 py-3 cursor-pointer hover:border-gray-300 hover:bg-gray-50 transition-all has-[:checked]:border-gray-400 has-[:checked]:bg-gray-50">
                        <input type="radio" name="is_active" value="0" {{ old('is_active') == '0' ? 'checked' : '' }} class="text-gray-600 focus:ring-gray-500">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Nonaktif</p>
                            <p class="text-xs text-gray-400">Disembunyikan sementara</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold text-sm transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan Metode
                </button>
                <a href="{{ route('admin.payment-methods.index') }}"
                   class="px-6 py-3 border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl font-medium text-sm transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
