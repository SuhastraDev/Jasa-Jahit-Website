@extends('layouts.admin')
@section('page-title', 'Jenis Pakaian')
@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Jenis Pakaian</h1>
            <p class="text-gray-500 text-sm mt-0.5">Kelola daftar jenis pakaian beserta foto referensi dan target gender di form pemesanan.</p>
        </div>
        <a href="{{ route('admin.clothing-types.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold text-sm transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Jenis Pakaian
        </a>
    </div>

    @if($clothingTypes->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-16 text-center">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Belum ada jenis pakaian</h3>
            <p class="text-gray-500 text-sm mb-6">Tambahkan jenis pakaian supaya muncul sebagai pilihan di form pemesanan.</p>
            <a href="{{ route('admin.clothing-types.create') }}" class="inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-blue-700 transition-colors">
                + Tambah Jenis Pertama
            </a>
        </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        @foreach($clothingTypes as $type)
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-all duration-200 group flex flex-col">

            <div class="relative h-40 bg-gray-100 overflow-hidden">
                @if($type->reference_image)
                    <img src="{{ Storage::url($type->reference_image) }}" alt="{{ $type->name }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                @else
                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                @endif
                <div class="absolute top-2.5 left-2.5">
                    <span class="bg-white/90 backdrop-blur-sm text-gray-700 text-[10px] font-semibold px-2 py-0.5 rounded-full shadow-sm border border-gray-100 capitalize">
                        {{ $type->gender }}
                    </span>
                </div>
            </div>

            <div class="p-4 flex-1 flex flex-col">
                <h4 class="font-semibold text-gray-900 text-sm truncate mb-3">{{ $type->name }}</h4>

                <div class="mt-auto flex items-center justify-between gap-2">
                    <form action="{{ route('admin.clothing-types.toggle', $type) }}" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                            class="px-2.5 py-1 text-xs font-semibold rounded-full border transition-colors {{ $type->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100' }}">
                            {{ $type->is_active ? 'Aktif' : 'Nonaktif' }}
                        </button>
                    </form>

                    <div class="flex items-center gap-1">
                        <a href="{{ route('admin.clothing-types.edit', $type) }}"
                           class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </a>
                        <form action="{{ route('admin.clothing-types.destroy', $type) }}" method="POST" class="inline"
                              onsubmit="return confirm('Hapus jenis pakaian \'{{ addslashes($type->name) }}\'?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="mt-6">{{ $clothingTypes->links() }}</div>
    @endif

</div>
@endsection
