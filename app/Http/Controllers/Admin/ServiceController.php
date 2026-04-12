<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $services = Service::latest()->paginate(10);
        return view('admin.services.index', compact('services'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.services.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:100',
            'type'           => 'required|in:custom,design,permak',
            'description'    => 'required|string',
            'base_price'     => 'required|numeric|min:0',
            'estimated_days' => 'required|integer|min:1',
            'is_active'      => 'required|boolean',
        ]);

        Service::create([
            'name'           => $request->name,
            'type'           => $request->type,
            'slug'           => Str::slug($request->name),
            'description'    => $request->description,
            'base_price'     => $request->base_price,
            'estimated_days' => $request->estimated_days,
            'is_active'      => $request->is_active,
        ]);

        return redirect()->route('admin.services.index')->with('success', 'Layanan berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Service $service)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Service $service)
    {
        return view('admin.services.edit', compact('service'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Service $service)
    {
        $request->validate([
            'name'           => 'required|string|max:100',
            'type'           => 'required|in:custom,design,permak',
            'description'    => 'required|string',
            'base_price'     => 'required|numeric|min:0',
            'estimated_days' => 'required|integer|min:1',
        ]);

        $service->update([
            'name'           => $request->name,
            'type'           => $request->type,
            'slug'           => Str::slug($request->name),
            'description'    => $request->description,
            'base_price'     => $request->base_price,
            'estimated_days' => $request->estimated_days,
        ]);

        return redirect()->route('admin.services.index')->with('success', 'Layanan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service)
    {
        $service->delete();
        return redirect()->route('admin.services.index')->with('success', 'Layanan berhasil dihapus.');
    }

    /**
     * Toggle active status.
     */
    public function toggle(Service $service)
    {
        $service->update(['is_active' => !$service->is_active]);
        return back()->with('success', 'Status layanan berhasil diubah.');
    }
}
