<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Catalog;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CatalogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $catalogs = Catalog::with('service')->latest()->paginate(10);
        return view('admin.catalogs.index', compact('catalogs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $services = Service::where('is_active', true)->get();
        return view('admin.catalogs.create', compact('services'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'is_active' => 'required|boolean',
        ]);

        $imagePath = $request->file('image')->store('catalogs', 'public');

        Catalog::create([
            'service_id' => $request->service_id,
            'name' => $request->name,
            'description' => $request->description,
            'image_path' => $imagePath,
            'is_active' => $request->is_active,
        ]);

        return redirect()->route('admin.catalogs.index')->with('success', 'Katalog desain berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Catalog $catalog)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Catalog $catalog)
    {
        $services = Service::where('is_active', true)->get();
        return view('admin.catalogs.edit', compact('catalog', 'services'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Catalog $catalog)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = [
            'service_id' => $request->service_id,
            'name' => $request->name,
            'description' => $request->description,
        ];

        if ($request->hasFile('image')) {
            if (Storage::disk('public')->exists($catalog->image_path)) {
                Storage::disk('public')->delete($catalog->image_path);
            }
            $data['image_path'] = $request->file('image')->store('catalogs', 'public');
        }

        $catalog->update($data);

        return redirect()->route('admin.catalogs.index')->with('success', 'Katalog desain berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Catalog $catalog)
    {
        if (Storage::disk('public')->exists($catalog->image_path)) {
            Storage::disk('public')->delete($catalog->image_path);
        }
        $catalog->delete();
        return redirect()->route('admin.catalogs.index')->with('success', 'Katalog desain berhasil dihapus.');
    }

    /**
     * Toggle active status.
     */
    public function toggle(Catalog $catalog)
    {
        $catalog->update(['is_active' => !$catalog->is_active]);
        return back()->with('success', 'Status katalog berhasil diubah.');
    }
}
