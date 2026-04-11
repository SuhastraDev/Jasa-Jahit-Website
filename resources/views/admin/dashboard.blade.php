@extends("layouts.admin")
@section('page-title', 'Dashboard')
@section("content")
<div class="py-8">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-sm text-gray-500 mt-0.5">Selamat datang kembali, <span class="font-semibold text-gray-700">{{ auth()->user()->name }}</span>! Berikut ringkasan bisnis Anda.</p>
            </div>
            <div class="text-sm text-gray-400 bg-white border border-gray-200 px-4 py-2 rounded-xl shadow-sm">
                {{ now()->translatedFormat('l, d F Y') }}
            </div>
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

            {{-- Total Pesanan --}}
            <div class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-6 overflow-hidden group hover:shadow-md transition-all duration-200">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-11 h-11 bg-blue-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full">+{{ $ordersThisMonth }} bln ini</span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900">{{ number_format($totalOrders) }}</p>
                    <p class="text-sm text-gray-500 mt-1">Total Pesanan</p>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-400 to-blue-600 rounded-b-2xl"></div>
            </div>

            {{-- Pendapatan --}}
            <div class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-6 overflow-hidden group hover:shadow-md transition-all duration-200">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-11 h-11 bg-emerald-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        @php $diff = $revenueLastMonth > 0 ? round(($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth * 100) : 0; @endphp
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $diff >= 0 ? 'text-emerald-600 bg-emerald-50' : 'text-red-600 bg-red-50' }}">
                            {{ $diff >= 0 ? '↑' : '↓' }} {{ abs($diff) }}%
                        </span>
                    </div>
                    <p class="text-2xl font-bold text-gray-900">Rp {{ number_format($revenueThisMonth, 0, ',', '.') }}</p>
                    <p class="text-sm text-gray-500 mt-1">Pendapatan Bulan Ini</p>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-400 to-emerald-600 rounded-b-2xl"></div>
            </div>

            {{-- Total User --}}
            <div class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-6 overflow-hidden group hover:shadow-md transition-all duration-200">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-11 h-11 bg-purple-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2.5 py-1 rounded-full">Aktif</span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900">{{ number_format($totalUsers) }}</p>
                    <p class="text-sm text-gray-500 mt-1">Total Pelanggan</p>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-400 to-purple-600 rounded-b-2xl"></div>
            </div>

            {{-- Menunggu Aksi --}}
            <div class="relative bg-white rounded-2xl shadow-sm border border-gray-100 p-6 overflow-hidden group hover:shadow-md transition-all duration-200">
                <div class="absolute inset-0 bg-gradient-to-br from-orange-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-11 h-11 bg-orange-100 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        @if($pendingPayments > 0)
                        <span class="text-xs font-semibold text-orange-600 bg-orange-50 px-2.5 py-1 rounded-full flex items-center gap-1">
                            <span class="w-1.5 h-1.5 bg-orange-500 rounded-full animate-pulse inline-block"></span>
                            Perlu Aksi
                        </span>
                        @endif
                    </div>
                    <p class="text-3xl font-bold text-gray-900">{{ $pendingPayments }}</p>
                    <p class="text-sm text-gray-500 mt-1">Menunggu Verifikasi</p>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-orange-400 to-orange-600 rounded-b-2xl"></div>
            </div>
        </div>

        {{-- Status Distribution --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4 sm:mb-5">
                <h3 class="font-bold text-gray-800">Distribusi Status Pesanan</h3>
                <a href="{{ route('admin.orders.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Lihat Semua →</a>
            </div>
            @php
                $statusMeta = [
                    'pending'    => ['label' => 'Pending',      'bg' => 'bg-yellow-50',  'text' => 'text-yellow-700', 'bar' => 'bg-yellow-400', 'border' => 'border-yellow-200'],
                    'confirmed'  => ['label' => 'Dikonfirmasi', 'bg' => 'bg-blue-50',    'text' => 'text-blue-700',   'bar' => 'bg-blue-400',   'border' => 'border-blue-200'],
                    'processing' => ['label' => 'Diproses',     'bg' => 'bg-indigo-50',  'text' => 'text-indigo-700', 'bar' => 'bg-indigo-400', 'border' => 'border-indigo-200'],
                    'done'       => ['label' => 'Selesai Buat', 'bg' => 'bg-purple-50',  'text' => 'text-purple-700', 'bar' => 'bg-purple-400', 'border' => 'border-purple-200'],
                    'shipped'    => ['label' => 'Dikirim',      'bg' => 'bg-orange-50',  'text' => 'text-orange-700', 'bar' => 'bg-orange-400', 'border' => 'border-orange-200'],
                    'completed'  => ['label' => 'Selesai',      'bg' => 'bg-emerald-50', 'text' => 'text-emerald-700','bar' => 'bg-emerald-400','border' => 'border-emerald-200'],
                    'cancelled'  => ['label' => 'Dibatalkan',   'bg' => 'bg-red-50',     'text' => 'text-red-700',   'bar' => 'bg-red-400',   'border' => 'border-red-200'],
                ];
                $totalForDistrib = array_sum(array_map(fn($k) => $ordersByStatus[$k] ?? 0, array_keys($statusMeta)));
            @endphp
            <div class="grid grid-cols-2 xs:grid-cols-3 sm:grid-cols-4 lg:grid-cols-7 gap-2 sm:gap-3">
                @foreach($statusMeta as $key => $meta)
                    @php $count = $ordersByStatus[$key] ?? 0; @endphp
                    <div class="{{ $meta['bg'] }} {{ $meta['border'] }} border rounded-xl p-4 text-center hover:shadow-sm transition-shadow">
                        <p class="text-2xl font-bold {{ $meta['text'] }}">{{ $count }}</p>
                        <p class="text-xs {{ $meta['text'] }} font-medium mt-1">{{ $meta['label'] }}</p>
                        <div class="mt-2 h-1 rounded-full bg-black/5">
                            <div class="{{ $meta['bar'] }} h-1 rounded-full transition-all" style="width: {{ $totalForDistrib > 0 ? round($count/$totalForDistrib*100) : 0 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <h3 class="font-bold text-gray-800">Pesanan per Bulan</h3>
                </div>
                <div class="relative h-64 w-full flex-1">
                    <canvas id="ordersChart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                    </div>
                    <h3 class="font-bold text-gray-800">Pendapatan per Bulan</h3>
                </div>
                <div class="relative h-64 w-full flex-1">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Tables Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Pesanan Terbaru --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-4 bg-blue-500 rounded-full"></div>
                        <h3 class="font-bold text-gray-800">Pesanan Terbaru</h3>
                    </div>
                    <a href="{{ route('admin.orders.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Lihat Semua →</a>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse($recentOrders as $order)
                    <div class="flex items-center gap-4 px-6 py-3.5 hover:bg-gray-50/70 transition-colors">
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-mono font-bold text-blue-600 hover:text-blue-800 text-xs block">{{ $order->order_code }}</a>
                            <p class="text-sm text-gray-700 truncate mt-0.5">{{ $order->user->name }}</p>
                        </div>
                        @php
                            $colors = ['yellow'=>'bg-yellow-100 text-yellow-800','blue'=>'bg-blue-100 text-blue-800','indigo'=>'bg-indigo-100 text-indigo-800','purple'=>'bg-purple-100 text-purple-800','orange'=>'bg-orange-100 text-orange-800','green'=>'bg-green-100 text-green-800','red'=>'bg-red-100 text-red-800','gray'=>'bg-gray-100 text-gray-800'];
                        @endphp
                        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full {{ $colors[$order->status_color] ?? 'bg-gray-100 text-gray-800' }} shrink-0">{{ $order->status_label }}</span>
                        <span class="text-xs text-gray-400 shrink-0 w-20 text-right">{{ $order->created_at->diffForHumans() }}</span>
                    </div>
                    @empty
                    <div class="px-6 py-10 text-center text-gray-400 text-sm">Belum ada pesanan.</div>
                    @endforelse
                </div>
            </div>

            {{-- Pembayaran Menunggu --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-4 bg-orange-500 rounded-full"></div>
                        <h3 class="font-bold text-gray-800">Pembayaran Menunggu</h3>
                    </div>
                    <a href="{{ route('admin.payments.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Lihat Semua →</a>
                </div>
                <div class="divide-y divide-gray-50">
                    @forelse($pendingPaymentsList as $payment)
                    <div class="flex items-center gap-4 px-6 py-3.5 hover:bg-gray-50/70 transition-colors">
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('admin.orders.show', $payment->order) }}" class="font-mono font-bold text-blue-600 hover:text-blue-800 text-xs block">{{ $payment->order->order_code }}</a>
                            <p class="text-sm text-gray-700 truncate mt-0.5">{{ $payment->order->user->name }}</p>
                        </div>
                        <span class="text-sm font-bold text-gray-800 shrink-0">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                        <span class="text-xs text-gray-400 shrink-0 w-20 text-right">{{ $payment->created_at->diffForHumans() }}</span>
                    </div>
                    @empty
                    <div class="px-6 py-10 text-center text-gray-400 text-sm">Tidak ada pembayaran menunggu.</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const labels = @json($chartLabels);

    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';

    // Orders Chart
    new Chart(document.getElementById('ordersChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Jumlah Pesanan',
                data: @json($chartData),
                backgroundColor: function(context) {
                    const chart = context.chart;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return 'rgba(59, 130, 246, 0.7)';
                    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(59, 130, 246, 0.85)');
                    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.4)');
                    return gradient;
                },
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 0,
                borderRadius: 10,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 10,
                    cornerRadius: 8,
                    titleFont: { size: 12 },
                    bodyFont: { size: 13, weight: 'bold' },
                }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0, color: '#9ca3af', font: { size: 11 } }, grid: { color: '#f3f4f6' }, border: { display: false } },
                x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 11 } }, border: { display: false } }
            }
        }
    });

    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: @json($revenueChartData),
                fill: true,
                backgroundColor: function(context) {
                    const chart = context.chart;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return 'rgba(16, 185, 129, 0.1)';
                    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(16, 185, 129, 0.25)');
                    gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');
                    return gradient;
                },
                borderColor: 'rgb(16, 185, 129)',
                borderWidth: 2.5,
                tension: 0.4,
                pointBackgroundColor: 'rgb(16, 185, 129)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2.5,
                pointRadius: 5,
                pointHoverRadius: 7,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return ' Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#9ca3af',
                        font: { size: 11 },
                        callback: function(value) {
                            if (value >= 1000000) return 'Rp ' + (value/1000000).toFixed(1) + 'jt';
                            if (value >= 1000) return 'Rp ' + (value/1000).toFixed(0) + 'rb';
                            return 'Rp ' + value;
                        }
                    },
                    grid: { color: '#f3f4f6' },
                    border: { display: false }
                },
                x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 11 } }, border: { display: false } }
            }
        }
    });
});
</script>
@endsection
