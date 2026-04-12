<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Catalog;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CatalogController extends Controller
{
    public function index()
    {
        $catalogs = Catalog::with('service')->latest()->paginate(10);
        return view('admin.catalogs.index', compact('catalogs'));
    }

    public function create()
    {
        $services = Service::where('is_active', true)->get();
        return view('admin.catalogs.create', compact('services'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'service_id'  => 'required|exists:services,id',
            'name'        => 'required|string|max:255',
            'description' => 'required|string',
            'image'       => 'required|image|max:10240',
            'is_active'   => 'required|in:0,1',
        ]);

        $imagePath = $request->file('image')->store('catalogs', 'public');

        Catalog::create([
            'service_id'  => $request->service_id,
            'name'        => $request->name,
            'description' => $request->description,
            'image_path'  => $imagePath,
            'is_active'   => (bool) $request->is_active,
        ]);

        return redirect()->route('admin.catalogs.index')
            ->with('success', 'Katalog desain berhasil ditambahkan.');
    }

    public function show(Catalog $catalog)
    {
        //
    }

    public function edit(Catalog $catalog)
    {
        $services = Service::where('is_active', true)->get();
        return view('admin.catalogs.edit', compact('catalog', 'services'));
    }

    public function update(Request $request, Catalog $catalog)
    {
        $request->validate([
            'service_id'  => 'required|exists:services,id',
            'name'        => 'required|string|max:255',
            'description' => 'required|string',
            'image'       => 'nullable|image|max:10240',
            'is_active'   => 'required|in:0,1',
        ]);

        $data = [
            'service_id'  => $request->service_id,
            'name'        => $request->name,
            'description' => $request->description,
            'is_active'   => (bool) $request->is_active,
        ];

        if ($request->hasFile('image')) {
            if ($catalog->image_path && Storage::disk('public')->exists($catalog->image_path)) {
                Storage::disk('public')->delete($catalog->image_path);
            }
            $data['image_path'] = $request->file('image')->store('catalogs', 'public');
        }

        $catalog->update($data);

        return redirect()->route('admin.catalogs.index')
            ->with('success', 'Katalog desain berhasil diperbarui.');
    }

    public function destroy(Catalog $catalog)
    {
        if ($catalog->image_path && Storage::disk('public')->exists($catalog->image_path)) {
            Storage::disk('public')->delete($catalog->image_path);
        }
        $catalog->delete();
        return redirect()->route('admin.catalogs.index')
            ->with('success', 'Katalog desain berhasil dihapus.');
    }

    public function toggle(Catalog $catalog)
    {
        $catalog->update(['is_active' => !$catalog->is_active]);
        return back()->with('success', 'Status katalog berhasil diubah.');
    }
}
