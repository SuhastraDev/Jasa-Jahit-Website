<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $danaNumber  = config('services.dana.number');
        $danaName    = config('services.dana.name');
        $qrExists    = Storage::disk('public')->exists('dana/qr_code.png');

        return view('admin.settings.index', compact('danaNumber', 'danaName', 'qrExists'));
    }

    public function uploadQr(Request $request)
    {
        $request->validate([
            'qr_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Hapus QR lama jika ada
        if (Storage::disk('public')->exists('dana/qr_code.png')) {
            Storage::disk('public')->delete('dana/qr_code.png');
        }

        // Simpan ke path tetap
        $request->file('qr_image')->storeAs('dana', 'qr_code.png', 'public');

        return back()->with('success', 'QR Code DANA berhasil diupload. Pelanggan sekarang bisa scan langsung.');
    }

    public function deleteQr()
    {
        Storage::disk('public')->delete('dana/qr_code.png');

        return back()->with('success', 'QR Code DANA berhasil dihapus.');
    }
}
