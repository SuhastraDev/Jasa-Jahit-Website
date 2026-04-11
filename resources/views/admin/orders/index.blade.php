@extends('layouts.admin')
@section('page-title', 'Kelola Pesanan')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Kelola Pesanan</h1>
            <p class="text-gray-500 text-sm mt-0.5">Pantau dan kelola semua pesanan pelanggan.</p>
        </div>
        @if($orders->total() > 0)
        <div class="bg-blue-50 border border-blue-100 text-blue-700 text-sm font-semibold px-4 py-2 rounded-xl self-start sm:self-auto">
            {{ $orders->total() }} pesanan ditemukan
        </div>
        @endif
    </div>

    {{-- Filter & Search --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 mb-6">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-end gap-3">
            <div class="flex-1 min-w-0 sm:min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Cari</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Kode atau nama pelanggan..."
                           class="w-full pl-9 rounded-xl border-gray-200 text-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-50">
                </div>
            </div>
            <div class="sm:w-44">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Status</label>
                <select name="status" class="w-full rounded-xl border-gray-200 text-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-50">
                    <option value="">Semua Status</option>
                    @foreach($statusOptions as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 sm:flex-none px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 transition-colors">Filter</button>
                <a href="{{ route('admin.orders.index') }}" class="flex-1 sm:flex-none px-5 py-2.5 bg-gray-100 text-gray-600 rounded-xl text-sm font-semibold hover:bg-gray-200 transition-colors text-center">Reset</a>
            </div>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Kode</th>
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pelanggan</th>
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Layanan</th>
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell whitespace-nowrap">Tanggal</th>
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Status</th>
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell whitespace-nowrap">Pembayaran</th>
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell whitespace-nowrap">Total</th>
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($orders as $order)
                    @php $isNew = $order->status === 'pending' && $order->created_at->gt(now()->subHours(24)); @endphp
                    <tr class="hover:bg-blue-50/20 transition-colors {{ $isNew ? 'bg-yellow-50/40' : '' }}">
                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                            <span class="text-xs sm:text-sm font-mono font-bold text-blue-600">{{ $order->order_code }}</span>
                            @if($isNew)
                                <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-500 text-white uppercase tracking-wide">Baru</span>
                            @endif
                        </td>
                        <td class="px-4 sm:px-6 py-4">
                            <div class="flex items-center gap-2 sm:gap-3">
                                <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-gradient-to-br from-slate-600 to-slate-800 flex items-center justify-center shrink-0">
                                    <span class="text-xs font-bold text-white">{{ strtoupper(substr($order->user->name, 0, 1)) }}</span>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-gray-900 truncate max-w-[120px] sm:max-w-none">{{ $order->user->name }}</div>
                                    <div class="text-xs text-gray-400 hidden sm:block">{{ $order->user->phone ?? $order->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 sm:px-6 py-4 hidden md:table-cell">
                            <span class="text-sm text-gray-600 whitespace-nowrap">{{ $order->service->name }}</span>
                        </td>
                        <td class="px-4 sm:px-6 py-4 hidden lg:table-cell whitespace-nowrap">
                            <div class="text-sm text-gray-700">{{ $order->created_at->format('d M Y') }}</div>
                            <div class="text-xs text-gray-400">{{ $order->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                            @php
                                $colors = [
                                    'yellow' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'blue'   => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'indigo' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                    'purple' => 'bg-purple-100 text-purple-800 border-purple-200',
                                    'orange' => 'bg-orange-100 text-orange-800 border-orange-200',
                                    'green'  => 'bg-green-100 text-green-800 border-green-200',
                                    'red'    => 'bg-red-100 text-red-800 border-red-200',
                                    'gray'   => 'bg-gray-100 text-gray-800 border-gray-200',
                                ];
                            @endphp
                            <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 inline-flex text-xs font-semibold rounded-full border {{ $colors[$order->status_color] ?? 'bg-gray-100 text-gray-800 border-gray-200' }}">
                                {{ $order->status_label }}
                            </span>
                        </td>
                        <td class="px-4 sm:px-6 py-4 hidden sm:table-cell whitespace-nowrap">
                            @php
                                $lp = $order->latestPayment;
                                if (!$order->total_price) {
                                    $payBadge = ['text' => 'Blm Ada Harga', 'class' => 'bg-gray-100 text-gray-500 border-gray-200'];
                                } elseif (!$lp) {
                                    $payBadge = ['text' => 'Belum Bayar', 'class' => 'bg-red-100 text-red-700 border-red-200'];
                                } elseif ($lp->status === 'pending') {
                                    $payBadge = ['text' => 'Menunggu Verif', 'class' => 'bg-orange-100 text-orange-700 border-orange-200'];
                                } elseif ($lp->status === 'verified') {
                                    $payBadge = ['text' => 'Lunas ✓', 'class' => 'bg-green-100 text-green-700 border-green-200'];
                                } else {
                                    $payBadge = ['text' => 'Ditolak', 'class' => 'bg-red-100 text-red-700 border-red-200'];
                                }
                            @endphp
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full border {{ $payBadge['class'] }}">{{ $payBadge['text'] }}</span>
                        </td>
                        <td class="px-4 sm:px-6 py-4 hidden md:table-cell whitespace-nowrap">
                            <span class="text-sm font-semibold text-gray-800">
                                {{ $order->total_price ? 'Rp ' . number_format($order->total_price, 0, ',', '.') : '-' }}
                            </span>
                        </td>
                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('admin.orders.show', $order) }}"
                               class="inline-flex items-center gap-1 px-2.5 py-1.5 sm:px-3 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                                Detail
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center">
                                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <p class="text-gray-500 font-medium">Belum ada pesanan masuk</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 sm:px-6 py-4 border-t border-gray-50">{{ $orders->links() }}</div>
    </div>
</div>
@endsection
