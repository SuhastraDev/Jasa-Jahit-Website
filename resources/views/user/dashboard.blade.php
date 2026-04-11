@extends('layouts.user')
@section('page-title', 'Dashboard')
@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

    {{-- Welcome Banner --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-700 rounded-2xl p-6 sm:p-8 mb-8 shadow-sm">
        <div class="absolute inset-0 opacity-10" style="background-image: repeating-linear-gradient(45deg, #fff 0, #fff 1px, transparent 0, transparent 50%); background-size: 20px 20px;"></div>
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-5">
            <div>
                <p class="text-blue-200 text-sm font-medium mb-1">Selamat datang kembali 👋</p>
                <h1 class="text-2xl sm:text-3xl font-bold text-white mb-1">{{ auth()->user()->name }}</h1>
                <p class="text-blue-200 text-sm">Kelola pesanan, ukur badan, dan pantau pengiriman Anda di sini.</p>
            </div>
            <a href="{{ route('user.orders.create') }}"
               class="inline-flex items-center gap-2 px-5 py-3 bg-white text-blue-700 rounded-xl font-bold text-sm hover:bg-blue-50 transition-colors shadow-sm self-start sm:self-auto flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Pesan Sekarang
            </a>
        </div>
    </div>

    {{-- Stats --}}
    @php
        $totalOrders = $orders->count();
        $activeOrders = $orders->whereIn('status', ['pending','confirmed','measuring','cutting','sewing','finishing','quality_check','ready_to_ship'])->count();
        $completedOrders = $orders->where('status', 'completed')->count();
        $cancelledOrders = $orders->where('status', 'cancelled')->count();
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $totalOrders }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Total Pesanan</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 bg-amber-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $activeOrders }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Sedang Diproses</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $completedOrders }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Selesai</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="w-9 h-9 bg-red-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $cancelledOrders }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Dibatalkan</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Recent Orders --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-50">
                <h2 class="font-bold text-gray-900">Pesanan Terbaru</h2>
                <a href="{{ route('user.orders.index') }}" class="text-xs text-blue-600 font-semibold hover:text-blue-800">Lihat Semua →</a>
            </div>
            @if($orders->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center px-6">
                    <div class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <p class="text-gray-500 font-medium mb-1">Belum ada pesanan</p>
                    <p class="text-sm text-gray-400 mb-4">Mulai pesan pakaian custom pertama Anda!</p>
                    <a href="{{ route('user.orders.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition-colors">Pesan Sekarang</a>
                </div>
            @else
                <div class="divide-y divide-gray-50">
                    @foreach($orders->take(5) as $order)
                    @php
                        $colors = [
                            'yellow' => 'bg-yellow-100 text-yellow-700',
                            'blue' => 'bg-blue-100 text-blue-700',
                            'indigo' => 'bg-indigo-100 text-indigo-700',
                            'purple' => 'bg-purple-100 text-purple-700',
                            'orange' => 'bg-orange-100 text-orange-700',
                            'green' => 'bg-green-100 text-green-700',
                            'red' => 'bg-red-100 text-red-700',
                            'gray' => 'bg-gray-100 text-gray-700',
                        ];
                    @endphp
                    <div class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50/60 transition-colors">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-mono text-sm font-bold text-gray-800">{{ $order->order_code }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $colors[$order->status_color] ?? 'bg-gray-100 text-gray-700' }}">{{ $order->status_label }}</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $order->service->name }} · {{ $order->created_at->format('d M Y') }}</p>
                        </div>
                        <a href="{{ route('user.orders.show', $order) }}" class="text-xs text-blue-600 font-semibold hover:text-blue-800 flex-shrink-0">Detail →</a>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Quick Actions --}}
        <div class="space-y-4">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-bold text-gray-900 mb-4">Aksi Cepat</h2>
                <div class="space-y-2">
                    <a href="{{ route('user.orders.create') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-blue-50 transition-colors group">
                        <div class="w-9 h-9 bg-blue-100 group-hover:bg-blue-200 rounded-xl flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Buat Pesanan</p>
                            <p class="text-xs text-gray-400">Jahit pakaian kustom Anda</p>
                        </div>
                    </a>
                    <a href="{{ route('user.measurement.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-purple-50 transition-colors group">
                        <div class="w-9 h-9 bg-purple-100 group-hover:bg-purple-200 rounded-xl flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Ukur Badan</p>
                            <p class="text-xs text-gray-400">Analisis AI dari foto</p>
                        </div>
                    </a>
                    <a href="{{ route('user.chat.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-green-50 transition-colors group">
                        <div class="w-9 h-9 bg-green-100 group-hover:bg-green-200 rounded-xl flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Chat Admin</p>
                            <p class="text-xs text-gray-400">Konsultasi pesanan Anda</p>
                        </div>
                    </a>
                </div>
            </div>

            {{-- Profile card --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-bold text-gray-900 mb-3">Profil Saya</h2>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center text-white font-bold text-lg">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <a href="{{ route('profile.edit') }}" class="flex items-center justify-center gap-2 w-full py-2 border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit Profil
                </a>
            </div>
        </div>

    </div>
</div>
@endsection
