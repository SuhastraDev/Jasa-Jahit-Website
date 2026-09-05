@extends('layouts.admin')
@section('page-title', 'Master Data Bahan')
@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Master Data Bahan</h1>
            <p class="text-gray-500 text-sm mt-0.5">Kelola pilihan bahan yang bisa dipilih pelanggan saat memesan, beserta biaya tambahannya.</p>
        </div>
        <a href="{{ route('admin.fabrics.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold text-sm transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Bahan
        </a>
    </div>

    @if($fabrics->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-16 text-center">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Belum ada bahan</h3>
            <p class="text-gray-500 text-sm mb-6">Tambahkan jenis bahan supaya pelanggan bisa memilihnya saat checkout.</p>
            <a href="{{ route('admin.fabrics.create') }}" class="inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-blue-700 transition-colors">
                + Tambah Bahan Pertama
            </a>
        </div>
    @else
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-5 py-3 font-semibold">Nama Bahan</th>
                    <th class="text-left px-5 py-3 font-semibold">Biaya Tambahan</th>
                    <th class="text-left px-5 py-3 font-semibold">Status</th>
                    <th class="text-right px-5 py-3 font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($fabrics as $fabric)
                <tr class="hover:bg-gray-50/60">
                    <td class="px-5 py-3 font-semibold text-gray-900">{{ $fabric->name }}</td>
                    <td class="px-5 py-3 text-gray-600">+Rp {{ number_format($fabric->price_addition, 0, ',', '.') }}</td>
                    <td class="px-5 py-3">
                        <form action="{{ route('admin.fabrics.toggle', $fabric) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                class="px-2.5 py-1 text-xs font-semibold rounded-full border transition-colors {{ $fabric->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100' }}">
                                {{ $fabric->is_active ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </form>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('admin.fabrics.edit', $fabric) }}"
                               class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <form action="{{ route('admin.fabrics.destroy', $fabric) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Hapus bahan \'{{ addslashes($fabric->name) }}\'?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $fabrics->links() }}</div>
    @endif

</div>
@endsection
