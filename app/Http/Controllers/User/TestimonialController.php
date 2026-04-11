<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    /**
     * Tampilkan form rating untuk pesanan yang sudah selesai.
     */
    public function create(Order $order)
    {
        // Pastikan pesanan milik user yang login
        if ((int) $order->user_id !== (int) auth()->id()) {
            abort(403);
        }

        // Hanya pesanan completed yang boleh diberi rating
        if ($order->status !== 'completed') {
            return back()->with('error', 'Pesanan belum selesai.');
        }

        // Cek apakah sudah pernah memberikan rating
        $existing = Testimonial::where('order_id', $order->id)
                               ->where('user_id', auth()->id())
                               ->first();

        if ($existing) {
            return back()->with('info', 'Anda sudah memberikan rating untuk pesanan ini.');
        }

        return view('user.testimonials.create', compact('order'));
    }

    /**
     * Simpan rating & testimoni.
     */
    public function store(Request $request, Order $order)
    {
        if ((int) $order->user_id !== (int) auth()->id()) {
            abort(403);
        }

        if ($order->status !== 'completed') {
            return back()->with('error', 'Pesanan belum selesai.');
        }

        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        Testimonial::create([
            'user_id'  => auth()->id(),
            'order_id' => $order->id,
            'rating'   => $request->rating,
            'comment'  => $request->comment,
        ]);

        return redirect()->route('user.orders.show', $order)
                         ->with('success', 'Terima kasih atas rating Anda! Testimoni akan muncul setelah disetujui admin.');
    }
}
