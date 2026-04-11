@extends('layouts.admin')
@section('page-title', 'Verifikasi Pembayaran')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Verifikasi Pembayaran</h1>
            <p class="text-gray-500 text-sm mt-0.5">Tinjau dan verifikasi bukti pembayaran dari pelanggan.</p>
        </div>
        @php $pendingCount = $payments->where('status', 'pending')->count(); @endphp
        @if($pendingCount > 0)
        <div class="bg-orange-50 border border-orange-100 text-orange-700 text-sm font-semibold px-4 py-2 rounded-xl flex items-center gap-2 self-start sm:self-auto">
            <span class="w-2 h-2 bg-orange-500 rounded-full animate-pulse"></span>
            {{ $pendingCount }} menunggu verifikasi
        </div>
        @endif
    </div>

    {{-- Filter --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5 mb-6">
        <form method="GET" action="{{ route('admin.payments.index') }}" class="flex flex-col sm:flex-row sm:items-end gap-3">
            <div class="sm:w-52">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">Status</label>
                <select name="status" class="w-full rounded-xl border-gray-200 text-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-50">
                    <option value="">Semua Status</option>
                    <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Menunggu</option>
                    <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>Terverifikasi</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 sm:flex-none px-5 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 transition-colors">Filter</button>
                <a href="{{ route('admin.payments.index') }}" class="flex-1 sm:flex-none px-5 py-2.5 bg-gray-100 text-gray-600 rounded-xl text-sm font-semibold hover:bg-gray-200 transition-colors text-center">Reset</a>
            </div>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Pesanan</th>
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pelanggan</th>
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Jumlah</th>
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden sm:table-cell">Tipe</th>
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Bukti</th>
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell whitespace-nowrap">Tanggal</th>
                        <th class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($payments as $payment)
                    <tr class="hover:bg-gray-50/60 transition-colors {{ $payment->status === 'pending' ? 'bg-orange-50/20' : '' }}">
                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('admin.orders.show', $payment->order) }}" class="font-mono font-bold text-blue-600 hover:text-blue-800 text-xs sm:text-sm">
                                {{ $payment->order->order_code }}
                            </a>
                        </td>
                        <td class="px-4 sm:px-6 py-4">
                            <div class="flex items-center gap-2 sm:gap-3">
                                <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-gradient-to-br from-slate-600 to-slate-800 flex items-center justify-center shrink-0">
                                    <span class="text-xs font-bold text-white">{{ strtoupper(substr($payment->order->user->name, 0, 1)) }}</span>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-gray-900 truncate max-w-[100px] sm:max-w-none">{{ $payment->order->user->name }}</div>
                                    <div class="text-xs text-gray-400 hidden sm:block">{{ $payment->order->user->phone ?? '-' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-gray-900">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                        </td>
                        <td class="px-4 sm:px-6 py-4 hidden sm:table-cell whitespace-nowrap">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full border {{ $payment->payment_type === 'full' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200' }}">
                                {{ $payment->payment_type === 'full' ? 'Lunas' : 'DP' }}
                            </span>
                        </td>
                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                            <div x-data="{ showModal: false }">
                                <button @click="showModal = true" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-700 text-white text-xs font-semibold rounded-lg hover:bg-slate-800 transition-colors whitespace-nowrap">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Lihat
                                </button>
                                <div x-show="showModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4" @click.self="showModal = false">
                                    <div class="bg-white rounded-2xl p-5 w-full max-w-lg max-h-[90vh] overflow-auto shadow-2xl">
                                        <div class="flex justify-between items-center mb-4">
                                            <div>
                                                <h4 class="font-bold text-gray-800">Bukti Transfer</h4>
                                                <p class="text-xs text-gray-500 font-mono mt-0.5">{{ $payment->order->order_code }}</p>
                                            </div>
                                            <button @click="showModal = false" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                        <img src="{{ Storage::url($payment->proof_image) }}" alt="Bukti Pembayaran" class="rounded-xl max-w-full shadow-sm">
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                            @php
                                $statusStyle = ['yellow'=>'bg-yellow-50 text-yellow-700 border-yellow-200','green'=>'bg-emerald-50 text-emerald-700 border-emerald-200','red'=>'bg-red-50 text-red-700 border-red-200'];
                            @endphp
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full border {{ $statusStyle[$payment->status_color] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                                {{ $payment->status_label }}
                            </span>
                            @if($payment->reject_reason)
                                <p class="text-xs text-red-500 mt-1 max-w-[100px] truncate" title="{{ $payment->reject_reason }}">{{ $payment->reject_reason }}</p>
                            @endif
                        </td>
                        <td class="px-4 sm:px-6 py-4 hidden lg:table-cell whitespace-nowrap">
                            <div class="text-sm text-gray-700">{{ $payment->created_at->format('d M Y') }}</div>
                            <div class="text-xs text-gray-400">{{ $payment->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                            @if($payment->status === 'pending')
                                <div class="flex flex-col gap-1.5 min-w-[100px]">
                                    <form action="{{ route('admin.payments.verify', $payment) }}" method="POST" onsubmit="return confirm('Verifikasi pembayaran ini?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-semibold hover:bg-emerald-700 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            Verifikasi
                                        </button>
                                    </form>
                                    <div x-data="{ showReject: false }">
                                        <button @click="showReject = !showReject" class="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 bg-red-600 text-white rounded-lg text-xs font-semibold hover:bg-red-700 transition-colors">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Tolak
                                        </button>
                                        <form x-show="showReject" x-transition action="{{ route('admin.payments.reject', $payment) }}" method="POST" class="mt-1.5 space-y-1">
                                            @csrf
                                            @method('PATCH')
                                            <textarea name="reject_reason" rows="2" required class="w-full rounded-lg border-gray-200 text-xs focus:border-red-500 focus:ring-red-500 bg-gray-50" placeholder="Alasan penolakan..."></textarea>
                                            <button type="submit" class="w-full px-3 py-1.5 bg-red-500 text-white rounded-lg text-xs font-semibold hover:bg-red-600 transition-colors">Kirim</button>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <span class="text-xs text-gray-400">
                                    @if($payment->verifiedBy) oleh {{ $payment->verifiedBy->name }} @else — @endif
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-14 h-14 bg-gray-100 rounded-2xl flex items-center justify-center">
                                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <p class="text-gray-500 font-medium">Tidak ada pembayaran</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 sm:px-6 py-4 border-t border-gray-50">{{ $payments->links() }}</div>
    </div>
</div>
@endsection
