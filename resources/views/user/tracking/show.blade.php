@extends('layouts.user')
@section('page-title', 'Lacak Pengiriman')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6">
        <a href="{{ route('user.orders.show', $order) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali ke Pesanan
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Lacak Pengiriman</h1>
    </div>

    {{-- Info Resi --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-5">
        <div class="flex flex-col sm:flex-row justify-between sm:items-start gap-4">
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1.5">Nomor Resi / AWB</p>
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="text-2xl font-mono font-bold text-gray-900">{{ $order->shipment->tracking_number }}</span>
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded-full uppercase tracking-wider">
                        {{ $order->shipment->expedition }}
                    </span>
                </div>
            </div>
            <div class="text-left sm:text-right">
                <p class="text-xs text-gray-400 font-medium mb-0.5">Dikirim pada</p>
                <p class="text-sm font-semibold text-gray-800">{{ $order->shipment->shipped_at->format('d M Y, H:i') }}</p>
            </div>
        </div>

        @if($tracking['is_mock'] ?? false)
        <div class="mt-4 pt-4 border-t border-gray-50">
            <div class="flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-amber-700 text-xs">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                Data tracking simulasi karena API Key RajaOngkir belum dikonfigurasi.
            </div>
        </div>
        @endif
    </div>

    {{-- Timeline --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="font-bold text-gray-900">Status Perjalanan</h2>
            <a href="{{ request()->fullUrl() }}"
               class="inline-flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg font-semibold transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Refresh
            </a>
        </div>

        @if(!$tracking['success'])
            <div class="text-center py-12">
                <div class="w-14 h-14 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <p class="text-gray-700 font-semibold whitespace-pre-line">{{ $tracking['error'] }}</p>
            </div>
        @else
            @php
                $status = $tracking['data']['summary']['status'] ?? ($tracking['summary']['status'] ?? '');
                $manifests = $tracking['data']['manifest'] ?? ($tracking['manifest'] ?? []);
                $manifests = is_array($manifests) ? array_reverse($manifests) : [];
            @endphp

            @if($status === 'DELIVERED')
                <div class="mb-6 bg-green-50 border border-green-200 rounded-2xl p-4 flex items-center gap-4">
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <p class="font-bold text-green-800">Paket Telah Diterima</p>
                        <p class="text-sm text-green-600 mt-0.5">Diterima oleh: <strong>{{ $tracking['data']['summary']['receiver_name'] ?? ($tracking['summary']['receiver_name'] ?? 'Tidak diketahui') }}</strong></p>
                    </div>
                </div>
            @endif

            @if(empty($manifests))
                <div class="text-center py-10">
                    <p class="text-gray-500 text-sm">Data tracking belum tersedia di database logistik kurir. Silakan tunggu beberapa saat.</p>
                </div>
            @else
                <div class="relative pl-6 sm:pl-8 py-2">
                    <div class="absolute left-[11px] sm:left-[15px] top-4 bottom-2 w-0.5 bg-gray-100"></div>
                    <div class="space-y-7">
                        @foreach($manifests as $index => $item)
                        <div class="relative">
                            <div class="absolute -left-[29px] sm:-left-[33px] mt-1">
                                <div class="w-4 h-4 rounded-full border-4 shadow-sm {{ $index === 0 ? 'bg-blue-600 border-blue-100' : 'bg-gray-200 border-white' }}"></div>
                            </div>
                            <div>
                                <div class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between mb-1 gap-1">
                                    <h4 class="text-sm font-bold {{ $index === 0 ? 'text-gray-900' : 'text-gray-700' }}">
                                        {{ $item['manifest_code'] ?? 'UPDATE' }}
                                    </h4>
                                    <span class="text-xs text-gray-400 font-medium">
                                        {{ \Carbon\Carbon::parse(($item['manifest_date'] ?? '') . ' ' . ($item['manifest_time'] ?? ''))->format('d M Y, H:i') }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 leading-relaxed">{{ $item['manifest_description'] ?? '' }}</p>
                                @if(!empty($item['city_name']))
                                    <p class="text-xs text-gray-400 mt-1 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                        {{ $item['city_name'] }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
