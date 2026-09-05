@extends('layouts.admin')
@section('page-title', 'Edit Bahan')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8">

    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.fabrics.index') }}"
           class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Bahan</h1>
            <p class="text-gray-500 text-sm mt-0.5">{{ $fabric->name }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <form action="{{ route('admin.fabrics.update', $fabric) }}" method="POST" class="p-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Nama Bahan <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name', $fabric->name) }}" required
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm @error('name') border-red-400 @enderror">
                @error('name')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="price_addition" class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Biaya Tambahan (Rp) <span class="text-red-500">*</span>
                </label>
                <input type="number" id="price_addition" name="price_addition" value="{{ old('price_addition', $fabric->price_addition) }}" required min="0" step="1000"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm @error('price_addition') border-red-400 @enderror">
                @error('price_addition')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <div class="flex gap-3">
                    <label class="flex-1 flex items-center gap-3 border border-gray-200 rounded-xl px-4 py-3 cursor-pointer hover:border-blue-300 hover:bg-blue-50/30 transition-all has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                        <input type="radio" name="is_active" value="1" {{ old('is_active', $fabric->is_active ? '1' : '0') == '1' ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Aktif</p>
                            <p class="text-xs text-gray-400">Tampil di pilihan checkout</p>
                        </div>
                    </label>
                    <label class="flex-1 flex items-center gap-3 border border-gray-200 rounded-xl px-4 py-3 cursor-pointer hover:border-gray-300 hover:bg-gray-50 transition-all has-[:checked]:border-gray-400 has-[:checked]:bg-gray-50">
                        <input type="radio" name="is_active" value="0" {{ old('is_active', $fabric->is_active ? '1' : '0') == '0' ? 'checked' : '' }} class="text-gray-600 focus:ring-gray-500">
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
                <a href="{{ route('admin.fabrics.index') }}"
                   class="px-6 py-3 border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl font-medium text-sm transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
