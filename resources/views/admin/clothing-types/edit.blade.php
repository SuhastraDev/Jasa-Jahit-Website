@extends('layouts.admin')
@section('page-title', 'Edit Jenis Pakaian')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8">

    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.clothing-types.index') }}"
           class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Jenis Pakaian</h1>
            <p class="text-gray-500 text-sm mt-0.5">{{ $clothingType->name }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <form action="{{ route('admin.clothing-types.update', $clothingType) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Nama Jenis Pakaian <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name', $clothingType->name) }}" required
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm @error('name') border-red-400 @enderror">
                @error('name')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Target Gender <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-3 gap-3">
                    @foreach(['unisex' => 'Unisex', 'pria' => 'Pria', 'wanita' => 'Wanita'] as $value => $label)
                    <label class="flex items-center justify-center gap-2 border border-gray-200 rounded-xl px-3 py-3 cursor-pointer hover:border-blue-300 hover:bg-blue-50/30 transition-all has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                        <input type="radio" name="gender" value="{{ $value }}" {{ old('gender', $clothingType->gender) === $value ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-semibold text-gray-800">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
                @error('gender')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div x-data="{ preview: '{{ $clothingType->reference_image ? Storage::url($clothingType->reference_image) : '' }}', fileName: '' }">
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Foto Referensi</label>
                <div class="border-2 border-dashed border-gray-200 rounded-xl p-5 text-center hover:border-blue-400 transition-colors cursor-pointer"
                     @click="$refs.fileInput.click()"
                     :class="preview ? 'border-blue-300 bg-blue-50/20' : 'bg-gray-50 hover:bg-blue-50/10'">

                    <template x-if="preview">
                        <div class="space-y-3">
                            <img :src="preview" class="max-h-48 mx-auto rounded-xl object-contain shadow-sm">
                            <p class="text-sm text-gray-500" x-show="fileName" x-text="fileName"></p>
                            <p class="text-xs text-blue-600 font-medium">Klik untuk ganti gambar</p>
                        </div>
                    </template>

                    <template x-if="!preview">
                        <div class="space-y-3">
                            <div class="w-12 h-12 bg-gray-200 rounded-xl flex items-center justify-center mx-auto">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700">Klik untuk upload gambar baru</p>
                                <p class="text-xs text-gray-400 mt-1">Kosongkan jika tidak ingin mengganti</p>
                            </div>
                        </div>
                    </template>

                    <input type="file" x-ref="fileInput" name="image" accept="image/*" class="hidden"
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
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <div class="flex gap-3">
                    <label class="flex-1 flex items-center gap-3 border border-gray-200 rounded-xl px-4 py-3 cursor-pointer hover:border-blue-300 hover:bg-blue-50/30 transition-all has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                        <input type="radio" name="is_active" value="1" {{ old('is_active', $clothingType->is_active ? '1' : '0') == '1' ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Aktif</p>
                            <p class="text-xs text-gray-400">Tampil di form pemesanan</p>
                        </div>
                    </label>
                    <label class="flex-1 flex items-center gap-3 border border-gray-200 rounded-xl px-4 py-3 cursor-pointer hover:border-gray-300 hover:bg-gray-50 transition-all has-[:checked]:border-gray-400 has-[:checked]:bg-gray-50">
                        <input type="radio" name="is_active" value="0" {{ old('is_active', $clothingType->is_active ? '1' : '0') == '0' ? 'checked' : '' }} class="text-gray-600 focus:ring-gray-500">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Nonaktif</p>
                            <p class="text-xs text-gray-400">Tidak tampil sementara</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold text-sm transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan Perubahan
                </button>
                <a href="{{ route('admin.clothing-types.index') }}"
                   class="px-6 py-3 border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl font-medium text-sm transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
