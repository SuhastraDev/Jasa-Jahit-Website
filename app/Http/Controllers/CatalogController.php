<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use App\Models\Service;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $services = Service::where('is_active', true)->get();

        $query = Catalog::with('service')->where('is_active', true);

        if ($request->has('service') && $request->service != '') {
            $query->where('service_id', $request->service);
        }

        $catalogs = $query->latest()->paginate(12);

        return view('catalogs.index', compact('catalogs', 'services'));
    }
}
