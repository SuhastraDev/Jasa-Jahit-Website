<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClothingTypeReference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClothingTypeController extends Controller
{
    public function index()
    {
        $clothingTypes = ClothingTypeReference::latest()->paginate(15);
        return view('admin.clothing-types.index', compact('clothingTypes'));
    }

    public function create()
    {
        return view('admin.clothing-types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255|unique:clothing_type_references,name',
            'gender'    => 'required|in:pria,wanita,unisex',
            'image'     => 'nullable|image|max:10240',
            'is_active' => 'required|in:0,1',
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('clothing-types', 'public')
            : null;

        ClothingTypeReference::create([
            'name'            => $request->name,
            'gender'          => $request->gender,
            'reference_image' => $imagePath,
            'is_active'       => (bool) $request->is_active,
        ]);

        return redirect()->route('admin.clothing-types.index')
            ->with('success', 'Jenis pakaian berhasil ditambahkan.');
    }

    public function edit(ClothingTypeReference $clothingType)
    {
        return view('admin.clothing-types.edit', compact('clothingType'));
    }

    public function update(Request $request, ClothingTypeReference $clothingType)
    {
        $request->validate([
            'name'      => 'required|string|max:255|unique:clothing_type_references,name,' . $clothingType->id,
            'gender'    => 'required|in:pria,wanita,unisex',
            'image'     => 'nullable|image|max:10240',
            'is_active' => 'required|in:0,1',
        ]);

        $data = [
            'name'      => $request->name,
            'gender'    => $request->gender,
            'is_active' => (bool) $request->is_active,
        ];

        if ($request->hasFile('image')) {
            if ($clothingType->reference_image && Storage::disk('public')->exists($clothingType->reference_image)) {
                Storage::disk('public')->delete($clothingType->reference_image);
            }
            $data['reference_image'] = $request->file('image')->store('clothing-types', 'public');
        }

        $clothingType->update($data);

        return redirect()->route('admin.clothing-types.index')
            ->with('success', 'Jenis pakaian berhasil diperbarui.');
    }

    public function destroy(ClothingTypeReference $clothingType)
    {
        if ($clothingType->reference_image && Storage::disk('public')->exists($clothingType->reference_image)) {
            Storage::disk('public')->delete($clothingType->reference_image);
        }
        $clothingType->delete();
        return redirect()->route('admin.clothing-types.index')
            ->with('success', 'Jenis pakaian berhasil dihapus.');
    }

    public function toggle(ClothingTypeReference $clothingType)
    {
        $clothingType->update(['is_active' => !$clothingType->is_active]);
        return back()->with('success', 'Status jenis pakaian berhasil diubah.');
    }
}
