@extends('layouts.admin')
@section('page-title', 'Edit Layanan')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8">

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.services.index') }}"
           class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Layanan</h1>
            <p class="text-gray-500 text-sm mt-0.5">Perbarui informasi layanan <span class="font-semibold text-gray-700">{{ $service->name }}</span></p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <span class="font-semibold text-gray-700 text-sm">Ubah Informasi Layanan</span>
            </div>
        </div>

        <form action="{{ route('admin.services.update', $service) }}" method="POST" class="p-6 space-y-5">
            @csrf
            @method('PUT')

            {{-- Nama --}}
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Nama Layanan <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name', $service->name) }}" required
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm @error('name') border-red-400 ring-2 ring-red-200 @enderror">
                @error('name')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Deskripsi --}}
            <div>
                <label for="description" class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Deskripsi <span class="text-red-500">*</span>
                </label>
                <textarea id="description" name="description" rows="4" required
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm resize-none @error('description') border-red-400 @enderror">{{ old('description', $service->description) }}</textarea>
                @error('description')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Harga & Estimasi --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="base_price" class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Harga Dasar <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-sm text-gray-500 font-medium pointer-events-none">Rp</span>
                        <input type="number" id="base_price" name="base_price" value="{{ old('base_price', $service->base_price) }}" required min="0"
                            class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm @error('base_price') border-red-400 @enderror">
                    </div>
                    @error('base_price')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="estimated_days" class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Estimasi (Hari) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" id="estimated_days" name="estimated_days" value="{{ old('estimated_days', $service->estimated_days) }}" required min="1"
                            class="w-full pl-4 pr-14 py-3 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm @error('estimated_days') border-red-400 @enderror">
                        <span class="absolute inset-y-0 right-0 pr-4 flex items-center text-sm text-gray-400 pointer-events-none">hari</span>
                    </div>
                    @error('estimated_days')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Status --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Status Publikasi</label>
                <div class="flex gap-3">
                    <label class="flex-1 flex items-center gap-3 border border-gray-200 rounded-xl px-4 py-3 cursor-pointer hover:border-blue-300 hover:bg-blue-50/30 transition-all has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                        <input type="radio" name="is_active" value="1" {{ old('is_active', $service->is_active) == '1' ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Aktif</p>
                            <p class="text-xs text-gray-400">Ditampilkan ke pelanggan</p>
                        </div>
                    </label>
                    <label class="flex-1 flex items-center gap-3 border border-gray-200 rounded-xl px-4 py-3 cursor-pointer hover:border-gray-300 hover:bg-gray-50 transition-all has-[:checked]:border-gray-400 has-[:checked]:bg-gray-50">
                        <input type="radio" name="is_active" value="0" {{ old('is_active', $service->is_active) == '0' ? 'checked' : '' }} class="text-gray-600 focus:ring-gray-500">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Nonaktif</p>
                            <p class="text-xs text-gray-400">Disembunyikan sementara</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold text-sm transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan Perubahan
                </button>
                <a href="{{ route('admin.services.index') }}"
                   class="px-6 py-3 border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl font-medium text-sm transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
