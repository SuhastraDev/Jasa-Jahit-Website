@extends('layouts.admin')
@section('page-title', 'Portfolio')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Kelola Portfolio</h1>
            <p class="text-gray-500 text-sm mt-1">Tambah dan kelola foto karya terbaik yang ditampilkan di landing page.</p>
        </div>
        <a href="{{ route('admin.portfolios.create') }}"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-semibold text-sm transition-colors shadow-sm flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Portfolio
        </a>
    </div>

    @if($portfolios->isEmpty())
        <!-- Empty state -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-16 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Belum ada portfolio</h3>
            <p class="text-gray-500 text-sm mb-6">Tambahkan foto karya terbaik Anda untuk ditampilkan di landing page.</p>
            <a href="{{ route('admin.portfolios.create') }}" class="inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Portfolio Pertama
            </a>
        </div>
    @else
        <!-- Stats -->
        <div class="mb-6">
            <span class="text-sm text-gray-500">Total <strong class="text-gray-900">{{ $portfolios->count() }}</strong> karya ditampilkan</span>
        </div>

        <!-- Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5" x-data="{ lightbox: false, src: '', title: '' }">

            <!-- Lightbox -->
            <div x-show="lightbox" x-cloak @click.self="lightbox=false" @keydown.escape.window="lightbox=false"
                 class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                <div class="relative max-w-2xl w-full">
                    <img :src="src" :alt="title" class="w-full rounded-2xl shadow-2xl max-h-[80vh] object-contain">
                    <button @click="lightbox=false" class="absolute -top-3 -right-3 w-8 h-8 bg-white rounded-full flex items-center justify-center text-gray-700 shadow-lg hover:bg-gray-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-4 rounded-b-2xl">
                        <p class="text-white font-semibold" x-text="title"></p>
                    </div>
                </div>
            </div>

            @foreach($portfolios as $item)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-shadow group">
                <!-- Image -->
                <div class="relative h-48 overflow-hidden bg-gray-100 cursor-pointer"
                     @click="lightbox=true; src='{{ asset('storage/' . preg_replace('#^public/#', '', $item->image_path)) }}'; title='{{ addslashes($item->title) }}'">
                    <img src="{{ asset('storage/' . preg_replace('#^public/#', '', $item->image_path)) }}" alt="{{ $item->title }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                        <div class="bg-white/90 backdrop-blur-sm rounded-full p-2 opacity-0 group-hover:opacity-100 transition-all duration-200 transform scale-75 group-hover:scale-100">
                            <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                        </div>
                    </div>
                </div>

                <!-- Card footer -->
                <div class="p-4 flex items-center justify-between">
                    <h4 class="font-semibold text-gray-900 text-sm truncate flex-1 mr-3">{{ $item->title }}</h4>
                    <form action="{{ route('admin.portfolios.destroy', $item->id) }}" method="POST"
                          onsubmit="return confirm('Hapus portofolio \'{{ addslashes($item->title) }}\'? Tindakan ini tidak dapat dibatalkan.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="flex items-center gap-1.5 text-xs text-red-600 hover:text-red-700 hover:bg-red-50 px-2.5 py-1.5 rounded-lg transition-colors font-medium flex-shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
