<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function index()
    {
        $portfolios = Portfolio::latest()->get();
        return view('admin.portfolios.index', compact('portfolios'));
    }

    public function create()
    {
        return view('admin.portfolios.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $path = $request->file('image')->store('portfolios', 'public');

        Portfolio::create([
            'title' => $request->title,
            'image_path' => $path
        ]);

        return redirect()->route('admin.portfolios.index');
    }

    public function destroy(Portfolio $portfolio)
    {
        \Storage::delete($portfolio->image_path);
        $portfolio->delete();
        return back();
    }
}
