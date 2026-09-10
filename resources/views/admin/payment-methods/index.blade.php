@extends('layouts.admin')
@section('page-title', 'Metode Pembayaran')
@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Metode Pembayaran</h1>
            <p class="text-gray-500 text-sm mt-0.5">Kelola daftar rekening/e-wallet yang bisa dipakai pelanggan untuk membayar pesanan.</p>
        </div>
        <a href="{{ route('admin.payment-methods.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold text-sm transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Metode
        </a>
    </div>

    @if($paymentMethods->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-16 text-center">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Belum ada metode pembayaran</h3>
            <p class="text-gray-500 text-sm mb-6">Tambahkan minimal 1 metode supaya pelanggan bisa membayar pesanan.</p>
            <a href="{{ route('admin.payment-methods.create') }}" class="inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:bg-blue-700 transition-colors">
                + Tambah Metode Pertama
            </a>
        </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($paymentMethods as $method)
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-all duration-200 flex flex-col">
            <div class="relative h-40 bg-gray-100 flex items-center justify-center overflow-hidden">
                @if($method->qr_image)
                    <img src="{{ Storage::url($method->qr_image) }}" alt="QR {{ $method->name }}"
                         class="h-full object-contain">
                @else
                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                @endif
            </div>
            <div class="p-4 flex-1 flex flex-col">
                <h4 class="font-semibold text-gray-900 text-sm mb-1">{{ $method->name }}</h4>
                <p class="text-xs text-gray-500 font-mono">{{ $method->account_number }}</p>
                <p class="text-xs text-gray-400 mb-3">a/n {{ $method->account_name }}</p>

                <div class="mt-auto flex items-center justify-between gap-2">
                    <form action="{{ route('admin.payment-methods.toggle', $method) }}" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                            class="px-2.5 py-1 text-xs font-semibold rounded-full border transition-colors {{ $method->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100' }}">
                            {{ $method->is_active ? 'Aktif' : 'Nonaktif' }}
                        </button>
                    </form>
                    <div class="flex items-center gap-1">
                        <a href="{{ route('admin.payment-methods.edit', $method) }}"
                           class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </a>
                        <form action="{{ route('admin.payment-methods.destroy', $method) }}" method="POST" class="inline"
                              onsubmit="return confirm('Hapus metode \'{{ addslashes($method->name) }}\'?')">
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
    <div class="mt-6">{{ $paymentMethods->links() }}</div>
    @endif

</div>
@endsection
