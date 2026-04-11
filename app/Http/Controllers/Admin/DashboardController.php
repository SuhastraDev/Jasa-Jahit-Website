<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\Message;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        // ── Statistik Pesanan ──
        $totalOrders      = Order::count();
        $ordersThisMonth  = Order::whereMonth('created_at', $now->month)
                                 ->whereYear('created_at', $now->year)
                                 ->count();

        $ordersByStatus = Order::selectRaw('status, COUNT(*) as total')
                               ->groupBy('status')
                               ->pluck('total', 'status')
                               ->toArray();

        // ── Pendapatan ──
        $revenueThisMonth = Payment::where('status', 'verified')
                                    ->whereMonth('created_at', $now->month)
                                    ->whereYear('created_at', $now->year)
                                    ->sum('amount');

        $revenueLastMonth = Payment::where('status', 'verified')
                                    ->whereMonth('created_at', $now->copy()->subMonth()->month)
                                    ->whereYear('created_at', $now->copy()->subMonth()->year)
                                    ->sum('amount');

        // ── Statistik Umum ──
        $totalUsers         = User::where('role', 'user')->count();
        $pendingPayments    = Payment::where('status', 'pending')->count();
        $unreadMessages     = Message::where('is_read', false)
                                      ->whereHas('chat', fn($q) => $q->whereNotNull('user_id'))
                                      ->where('sender_id', '!=', auth()->id())
                                      ->count();

        // ── Data Tabel ──
        $recentOrders = Order::with('user', 'service')
                             ->latest()
                             ->take(5)
                             ->get();

        $pendingPaymentsList = Payment::with('order.user')
                                      ->where('status', 'pending')
                                      ->latest()
                                      ->take(5)
                                      ->get();

        // ── Data Chart: Pesanan per bulan (last 6 months) ──
        $chartLabels = [];
        $chartData   = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $chartLabels[] = $month->translatedFormat('M Y');
            $chartData[]   = Order::whereMonth('created_at', $month->month)
                                   ->whereYear('created_at', $month->year)
                                   ->count();
        }

        // ── Data Chart: Pendapatan per bulan (last 6 months) ──
        $revenueChartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $revenueChartData[] = (int) Payment::where('status', 'verified')
                                                ->whereMonth('created_at', $month->month)
                                                ->whereYear('created_at', $month->year)
                                                ->sum('amount');
        }

        return view('admin.dashboard', compact(
            'totalOrders',
            'ordersThisMonth',
            'ordersByStatus',
            'revenueThisMonth',
            'revenueLastMonth',
            'totalUsers',
            'pendingPayments',
            'unreadMessages',
            'recentOrders',
            'pendingPaymentsList',
            'chartLabels',
            'chartData',
            'revenueChartData'
        ));
    }
}
