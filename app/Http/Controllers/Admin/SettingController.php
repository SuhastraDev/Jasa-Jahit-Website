<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $danaNumber = Setting::get('dana_number');
        $danaName   = Setting::get('dana_name');
        $qrExists   = Storage::disk('public')->exists('dana/qr_code.png');

        return view('admin.settings.index', compact('danaNumber', 'danaName', 'qrExists'));
    }

    public function saveDana(Request $request)
    {
        $request->validate([
            'dana_number' => 'required|string|max:20',
            'dana_name'   => 'required|string|max:100',
        ]);

        Setting::set('dana_number', $request->dana_number);
        Setting::set('dana_name',   $request->dana_name);

        return back()->with('success', 'Nomor & nama DANA berhasil disimpan.');
    }

    public function uploadQr(Request $request)
    {
        $request->validate([
            'qr_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if (Storage::disk('public')->exists('dana/qr_code.png')) {
            Storage::disk('public')->delete('dana/qr_code.png');
        }

        $request->file('qr_image')->storeAs('dana', 'qr_code.png', 'public');

        return back()->with('success', 'QR Code DANA berhasil diupload. Pelanggan sekarang bisa scan langsung.');
    }

    public function deleteQr()
    {
        Storage::disk('public')->delete('dana/qr_code.png');

        return back()->with('success', 'QR Code DANA berhasil dihapus.');
    }
}
