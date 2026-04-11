<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Portfolio;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index()
    {
        $services = Service::where('is_active', true)->get();
        $portfolios = Portfolio::where('is_active', true)->get();
        $testimonials = Testimonial::where('is_approved', true)->with('user', 'order.service')->get();

        return view('landing.index', compact('services', 'portfolios', 'testimonials'));
    }
}
