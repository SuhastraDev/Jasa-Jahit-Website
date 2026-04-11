<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    public function index()
    {
        $testimonials = Testimonial::with('user', 'order.service')->latest()->get();
        return view('admin.testimonials.index', compact('testimonials'));
    }

    public function update(Request $request, Testimonial $testimonial)
    {
        $testimonial->update(['is_approved' => true]);
        return back();
    }

    public function destroy(Testimonial $testimonial)
    {
        $testimonial->delete();
        return back();
    }
}
