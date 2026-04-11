<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Admin\PortfolioController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\User\OrderController as UserOrderController;
use App\Http\Controllers\User\PaymentController as UserPaymentController;
use App\Http\Controllers\User\MeasurementController;
use App\Http\Controllers\User\TrackingController;
use App\Http\Controllers\User\ChatController as UserChatController;
use App\Http\Controllers\User\TestimonialController as UserTestimonialController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ShipmentController as AdminShipmentController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/katalog', [\App\Http\Controllers\CatalogController::class, 'index'])->name('catalogs.public');

// User routes
Route::middleware(['auth', 'verified', 'user'])->group(function () {
    Route::get('/dashboard', function () {
        $orders = auth()->user()->orders()->with('service')->latest()->get();
        return view('user.dashboard', compact('orders'));
    })->name('dashboard');

    // === Pesanan User ===
    Route::get('/pesan', [UserOrderController::class, 'create'])->name('user.orders.create');
    Route::post('/pesan', [UserOrderController::class, 'store'])->name('user.orders.store');
    Route::get('/pesanan-saya', [UserOrderController::class, 'index'])->name('user.orders.index');
    Route::get('/pesanan-saya/{order}', [UserOrderController::class, 'show'])->name('user.orders.show');
    Route::get('/pesanan-saya/{order}/tracking', [TrackingController::class, 'show'])->name('user.tracking.show');
    Route::post('/pesanan-saya/{order}/konfirmasi-terima', [UserOrderController::class, 'confirmReceipt'])->name('user.orders.confirm');
    Route::post('/pesanan-saya/{order}/laporkan-masalah', [UserOrderController::class, 'reportIssue'])->name('user.orders.report');

    // === Pembayaran User ===
    Route::get('/pesanan-saya/{order}/bayar', [UserPaymentController::class, 'create'])->name('user.payment.create');
    Route::post('/pesanan-saya/{order}/bayar', [UserPaymentController::class, 'store'])->name('user.payment.store');

    // === Ukur Badan (CV) ===
    Route::get('/ukur-badan', [MeasurementController::class, 'index'])->name('user.measurement.index');
    Route::post('/ukur-badan/analisis', [MeasurementController::class, 'analyze'])->name('user.measurement.analyze');
    Route::post('/ukur-badan/simpan', [MeasurementController::class, 'store'])->name('user.measurement.store');
    Route::delete('/ukur-badan/{measurement}', [MeasurementController::class, 'destroy'])->name('user.measurement.destroy');

    // === Chat User ===
    Route::get('/chat', [UserChatController::class, 'index'])->name('user.chat.index');
    Route::get('/chat/poll', [UserChatController::class, 'fetchMessages'])->name('user.chat.poll');
    Route::get('/chat/unread-count', [UserChatController::class, 'unreadCount'])->name('user.chat.unread');
    Route::get('/chat/admin-status', [UserChatController::class, 'adminStatus'])->name('user.chat.admin-status');
    Route::post('/chat/send', [UserChatController::class, 'store'])->name('user.chat.send');
    Route::delete('/chat/message/{message}', [UserChatController::class, 'destroyMessage'])->name('user.chat.message.destroy');
    Route::delete('/chat/messages', [UserChatController::class, 'destroyMessages'])->name('user.chat.messages.destroy');

    // === Testimoni User ===
    Route::get('/pesanan-saya/{order}/rating', [UserTestimonialController::class, 'create'])->name('user.testimonials.create');
    Route::post('/pesanan-saya/{order}/rating', [UserTestimonialController::class, 'store'])->name('user.testimonials.store');
});

// Admin routes
Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    Route::resource('admin/portfolios', PortfolioController::class)->names([
        'index' => 'admin.portfolios.index',
        'create' => 'admin.portfolios.create',
        'store' => 'admin.portfolios.store',
        'destroy' => 'admin.portfolios.destroy',
    ])->except(['show', 'edit', 'update']);

    Route::resource('admin/testimonials', TestimonialController::class)->names([
        'index' => 'admin.testimonials.index',
        'update' => 'admin.testimonials.update',
        'destroy' => 'admin.testimonials.destroy',
    ])->only(['index', 'update', 'destroy']);

    Route::patch('admin/services/{service}/toggle', [ServiceController::class, 'toggle'])->name('admin.services.toggle');
    Route::resource('admin/services', ServiceController::class)->names([
        'index' => 'admin.services.index',
        'create' => 'admin.services.create',
        'store' => 'admin.services.store',
        'edit' => 'admin.services.edit',
        'update' => 'admin.services.update',
        'destroy' => 'admin.services.destroy',
    ]);

    Route::patch('admin/catalogs/{catalog}/toggle', [CatalogController::class, 'toggle'])->name('admin.catalogs.toggle');
    Route::resource('admin/catalogs', CatalogController::class)->names([
        'index' => 'admin.catalogs.index',
        'create' => 'admin.catalogs.create',
        'store' => 'admin.catalogs.store',
        'edit' => 'admin.catalogs.edit',
        'update' => 'admin.catalogs.update',
        'destroy' => 'admin.catalogs.destroy',
    ]);

    // === Pesanan Admin ===
    Route::get('/admin/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/admin/orders/{order}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::patch('/admin/orders/{order}/update-status', [AdminOrderController::class, 'updateStatus'])->name('admin.orders.updateStatus');
    Route::post('/admin/orders/{order}/shipments', [AdminShipmentController::class, 'store'])->name('admin.shipments.store');

    // === Pembayaran Admin ===
    Route::get('/admin/payments', [AdminPaymentController::class, 'index'])->name('admin.payments.index');
    Route::patch('/admin/payments/{payment}/verify', [AdminPaymentController::class, 'verify'])->name('admin.payments.verify');
    Route::patch('/admin/payments/{payment}/reject', [AdminPaymentController::class, 'reject'])->name('admin.payments.reject');

    // === Chat Admin ===
    Route::get('/admin/chat', [AdminChatController::class, 'index'])->name('admin.chat.index');
    Route::get('/admin/chat/unread-counts', [AdminChatController::class, 'unreadCounts'])->name('admin.chat.unread');
    Route::get('/admin/chat/user-status/{user}', [AdminChatController::class, 'userStatus'])->name('admin.chat.user-status');
    Route::get('/admin/chat/{chat}/poll', [AdminChatController::class, 'fetchMessages'])->name('admin.chat.poll');
    Route::post('/admin/chat/{chat}/send', [AdminChatController::class, 'store'])->name('admin.chat.send');
    Route::delete('/admin/chat/message/{message}', [AdminChatController::class, 'destroyMessage'])->name('admin.chat.message.destroy');
    Route::delete('/admin/chat/{chat}/messages', [AdminChatController::class, 'destroyMessages'])->name('admin.chat.messages.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
