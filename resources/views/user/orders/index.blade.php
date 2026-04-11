@extends('layouts.user')
@section('page-title', 'Pesanan Saya')
@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Pesanan Saya</h1>
            <p class="text-gray-500 text-sm mt-0.5">Riwayat dan status semua pesanan Anda.</p>
        </div>
        <a href="{{ route('user.orders.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-xl font-semibold text-sm hover:bg-blue-700 transition-colors shadow-sm self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Pesan Sekarang
        </a>
    </div>

    @if($orders->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-16 text-center">
            <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Belum ada pesanan</h3>
            <p class="text-gray-500 text-sm mb-6">Mulai buat pesanan pertama Anda sekarang!</p>
            <a href="{{ route('user.orders.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Buat Pesanan Pertama
            </a>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/50">
                            <th class="px-4 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pesanan</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Tanggal</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 sm:px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Total</th>
                            <th class="px-4 sm:px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($orders as $order)
                        @php
                            $colors = [
                                'yellow' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                'blue' => 'bg-blue-100 text-blue-700 border-blue-200',
                                'indigo' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                                'purple' => 'bg-purple-100 text-purple-700 border-purple-200',
                                'orange' => 'bg-orange-100 text-orange-700 border-orange-200',
                                'green' => 'bg-green-100 text-green-700 border-green-200',
                                'red' => 'bg-red-100 text-red-700 border-red-200',
                                'gray' => 'bg-gray-100 text-gray-600 border-gray-200',
                            ];
                        @endphp
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            <td class="px-4 sm:px-6 py-4">
                                <div class="font-mono text-sm font-bold text-gray-900">{{ $order->order_code }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ $order->service->name }}</div>
                                {{-- Tanggal di mobile --}}
                                <div class="text-xs text-gray-400 mt-0.5 sm:hidden">{{ $order->created_at->format('d M Y') }}</div>
                            </td>
                            <td class="px-4 sm:px-6 py-4 hidden sm:table-cell whitespace-nowrap text-sm text-gray-500">
                                {{ $order->created_at->format('d M Y') }}
                            </td>
                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full border {{ $colors[$order->status_color] ?? 'bg-gray-100 text-gray-600 border-gray-200' }}">
                                    {{ $order->status_label }}
                                </span>
                                {{-- Total di mobile --}}
                                <div class="text-xs text-gray-500 mt-1 md:hidden font-medium">
                                    {{ $order->total_price ? 'Rp ' . number_format($order->total_price, 0, ',', '.') : '—' }}
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-4 hidden md:table-cell whitespace-nowrap text-sm text-gray-700 font-medium">
                                {{ $order->total_price ? 'Rp ' . number_format($order->total_price, 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right">
                                <a href="{{ route('user.orders.show', $order) }}"
                                   class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition-colors">
                                    Detail
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 sm:px-6 py-4 border-t border-gray-50">
                {{ $orders->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
