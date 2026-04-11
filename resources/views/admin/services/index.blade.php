@extends('layouts.admin')
@section('page-title', 'Layanan')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Data Layanan</h1>
            <p class="text-gray-500 text-sm mt-0.5">Kelola jenis layanan jahit yang tersedia untuk pelanggan.</p>
        </div>
        <a href="{{ route('admin.services.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold text-sm transition-colors shadow-sm self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Layanan
        </a>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Layanan</th>
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell whitespace-nowrap">Harga Dasar</th>
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Estimasi</th>
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 sm:px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($services as $service)
                    <tr class="hover:bg-blue-50/20 transition-colors">
                        <td class="px-4 sm:px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900 text-sm">{{ $service->name }}</p>
                                    {{-- Harga tampil di mobile sbg sub-info --}}
                                    <p class="text-xs text-blue-600 font-medium mt-0.5 sm:hidden">Rp {{ number_format($service->base_price, 0, ',', '.') }}</p>
                                    @if($service->description)
                                    <p class="text-xs text-gray-400 mt-0.5 truncate max-w-[180px] sm:max-w-xs hidden sm:block">{{ Str::limit($service->description, 60) }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 sm:px-6 py-4 hidden sm:table-cell whitespace-nowrap">
                            <span class="text-sm font-bold text-gray-900">Rp {{ number_format($service->base_price, 0, ',', '.') }}</span>
                        </td>
                        <td class="px-4 sm:px-6 py-4 hidden md:table-cell whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5 text-sm text-gray-700">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $service->estimated_days }} hari
                            </div>
                        </td>
                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                            <form action="{{ route('admin.services.toggle', $service) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="px-2.5 py-1 text-xs font-semibold rounded-full border transition-colors {{ $service->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100' }}">
                                    <span class="flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $service->is_active ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                        {{ $service->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </button>
                            </form>
                        </td>
                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="{{ route('admin.services.edit', $service) }}"
                                   class="p-1.5 sm:px-3 sm:py-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors sm:inline-flex sm:items-center sm:gap-1.5 sm:text-xs sm:font-semibold sm:bg-gray-100">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    <span class="hidden sm:inline">Edit</span>
                                </a>
                                <form action="{{ route('admin.services.destroy', $service) }}" method="POST" class="inline" onsubmit="return confirm('Hapus layanan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 sm:px-3 sm:py-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors sm:inline-flex sm:items-center sm:gap-1.5 sm:text-xs sm:font-semibold">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        <span class="hidden sm:inline">Hapus</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center">
                                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                                </div>
                                <p class="text-gray-500 font-medium">Belum ada layanan</p>
                                <a href="{{ route('admin.services.create') }}" class="text-blue-600 text-sm font-semibold hover:text-blue-700">+ Tambah layanan pertama</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 sm:px-6 py-4 border-t border-gray-50">{{ $services->links() }}</div>
    </div>
</div>
@endsection
