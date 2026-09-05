<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fabric;
use Illuminate\Http\Request;

class FabricController extends Controller
{
    public function index()
    {
        $fabrics = Fabric::latest()->paginate(15);
        return view('admin.fabrics.index', compact('fabrics'));
    }

    public function create()
    {
        return view('admin.fabrics.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255|unique:fabrics,name',
            'price_addition'  => 'required|numeric|min:0',
            'is_active'       => 'required|in:0,1',
        ]);

        Fabric::create([
            'name'           => $request->name,
            'price_addition' => $request->price_addition,
            'is_active'      => (bool) $request->is_active,
        ]);

        return redirect()->route('admin.fabrics.index')
            ->with('success', 'Bahan berhasil ditambahkan.');
    }

    public function edit(Fabric $fabric)
    {
        return view('admin.fabrics.edit', compact('fabric'));
    }

    public function update(Request $request, Fabric $fabric)
    {
        $request->validate([
            'name'            => 'required|string|max:255|unique:fabrics,name,' . $fabric->id,
            'price_addition'  => 'required|numeric|min:0',
            'is_active'       => 'required|in:0,1',
        ]);

        $fabric->update([
            'name'           => $request->name,
            'price_addition' => $request->price_addition,
            'is_active'      => (bool) $request->is_active,
        ]);

        return redirect()->route('admin.fabrics.index')
            ->with('success', 'Bahan berhasil diperbarui.');
    }

    public function destroy(Fabric $fabric)
    {
        $fabric->delete();
        return redirect()->route('admin.fabrics.index')
            ->with('success', 'Bahan berhasil dihapus.');
    }

    public function toggle(Fabric $fabric)
    {
        $fabric->update(['is_active' => !$fabric->is_active]);
        return back()->with('success', 'Status bahan berhasil diubah.');
    }
}
