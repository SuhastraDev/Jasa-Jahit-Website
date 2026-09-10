<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $paymentMethods = PaymentMethod::latest()->paginate(15);
        return view('admin.payment-methods.index', compact('paymentMethods'));
    }

    public function create()
    {
        return view('admin.payment-methods.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_name'   => 'required|string|max:255',
            'qr_image'       => 'nullable|image|max:2048',
            'instructions'   => 'nullable|string|max:1000',
            'is_active'      => 'required|in:0,1',
        ]);

        $qrPath = $request->hasFile('qr_image')
            ? $request->file('qr_image')->store('payment-methods', 'public')
            : null;

        PaymentMethod::create([
            'name'           => $request->name,
            'account_number' => $request->account_number,
            'account_name'   => $request->account_name,
            'qr_image'       => $qrPath,
            'instructions'   => $request->instructions,
            'is_active'      => (bool) $request->is_active,
        ]);

        return redirect()->route('admin.payment-methods.index')
            ->with('success', 'Metode pembayaran berhasil ditambahkan.');
    }

    public function edit(PaymentMethod $paymentMethod)
    {
        return view('admin.payment-methods.edit', compact('paymentMethod'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_name'   => 'required|string|max:255',
            'qr_image'       => 'nullable|image|max:2048',
            'instructions'   => 'nullable|string|max:1000',
            'is_active'      => 'required|in:0,1',
        ]);

        $data = [
            'name'           => $request->name,
            'account_number' => $request->account_number,
            'account_name'   => $request->account_name,
            'instructions'   => $request->instructions,
            'is_active'      => (bool) $request->is_active,
        ];

        if ($request->hasFile('qr_image')) {
            if ($paymentMethod->qr_image && Storage::disk('public')->exists($paymentMethod->qr_image)) {
                Storage::disk('public')->delete($paymentMethod->qr_image);
            }
            $data['qr_image'] = $request->file('qr_image')->store('payment-methods', 'public');
        }

        $paymentMethod->update($data);

        return redirect()->route('admin.payment-methods.index')
            ->with('success', 'Metode pembayaran berhasil diperbarui.');
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        if ($paymentMethod->qr_image && Storage::disk('public')->exists($paymentMethod->qr_image)) {
            Storage::disk('public')->delete($paymentMethod->qr_image);
        }
        $paymentMethod->delete();

        return redirect()->route('admin.payment-methods.index')
            ->with('success', 'Metode pembayaran berhasil dihapus.');
    }

    public function toggle(PaymentMethod $paymentMethod)
    {
        $paymentMethod->update(['is_active' => !$paymentMethod->is_active]);
        return back()->with('success', 'Status metode pembayaran berhasil diubah.');
    }
}
