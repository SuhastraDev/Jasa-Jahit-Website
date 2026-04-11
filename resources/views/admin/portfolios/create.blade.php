@extends('layouts.admin')
@section('page-title', 'Tambah Portfolio')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8">

    <!-- Header -->
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.portfolios.index') }}"
           class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Tambah Portfolio</h1>
            <p class="text-gray-500 text-sm mt-0.5">Upload foto karya baru untuk ditampilkan di landing page</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <form action="{{ route('admin.portfolios.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-semibold text-gray-700 mb-1.5">Judul Karya</label>
                <input type="text" id="title" name="title" value="{{ old('title') }}"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all @error('title') border-red-400 @enderror"
                    placeholder="Contoh: Kemeja Formal Biru Navy" required>
                @error('title')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Image Upload -->
            <div x-data="{ preview: null, fileName: '' }">
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Foto Karya</label>
                <div class="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-blue-400 transition-colors cursor-pointer"
                     @click="$refs.fileInput.click()"
                     :class="preview ? 'border-blue-300 bg-blue-50/30' : 'bg-gray-50'">

                    <!-- Preview -->
                    <template x-if="preview">
                        <div class="space-y-3">
                            <img :src="preview" class="max-h-48 mx-auto rounded-xl object-contain shadow-sm">
                            <p class="text-sm text-gray-600" x-text="fileName"></p>
                            <p class="text-xs text-blue-600 font-medium">Klik untuk ganti gambar</p>
                        </div>
                    </template>

                    <!-- Placeholder -->
                    <template x-if="!preview">
                        <div class="space-y-3">
                            <div class="w-12 h-12 bg-gray-200 rounded-xl flex items-center justify-center mx-auto">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700">Klik untuk upload foto</p>
                                <p class="text-xs text-gray-400 mt-1">PNG, JPG, WEBP — Maks. 2MB</p>
                            </div>
                        </div>
                    </template>

                    <input type="file" x-ref="fileInput" name="image" accept="image/*" class="hidden" required
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
                @error('image')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                    class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-semibold transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    Upload & Simpan
                </button>
                <a href="{{ route('admin.portfolios.index') }}"
                   class="flex-1 sm:flex-none text-center border border-gray-200 text-gray-600 hover:bg-gray-50 px-8 py-3 rounded-xl font-medium transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
