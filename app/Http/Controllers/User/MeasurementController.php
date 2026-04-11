<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Measurement;
use App\Services\CVMeasurementService;
use Illuminate\Http\Request;

class MeasurementController extends Controller
{
    /**
     * Halaman ukur badan — panduan + form upload
     */
    public function index()
    {
        $measurements = Measurement::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('user.measurement.index', compact('measurements'));
    }

    /**
     * Proses analisis CV dan tampilkan hasil
     */
    public function analyze(Request $request, CVMeasurementService $cvService)
    {
        $request->validate([
            'body_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'ref_object' => 'required|in:a4,atm,custom',
            'ref_width_cm' => 'required_if:ref_object,custom|nullable|numeric|min:1',
            'ref_height_cm' => 'required_if:ref_object,custom|nullable|numeric|min:1',
        ]);

        // Store the photo
        $photoPath = $request->file('body_photo')->store('measurements/' . auth()->id(), 'public');

        // Call CV service
        $result = $cvService->measure(
            $request->file('body_photo'),
            $request->ref_object,
            $request->ref_width_cm,
            $request->ref_height_cm,
        );

        if (!$result['success']) {
            return back()
                ->withInput()
                ->with('error', $result['error']);
        }

        $data = $result['data'];
        $refSize = null;
        if ($request->ref_object === 'custom') {
            $refSize = $request->ref_width_cm . 'x' . $request->ref_height_cm . 'cm';
        }

        return view('user.measurement.result', [
            'data' => $data,
            'confidence' => $result['confidence'] ?? 0,
            'refDetected' => $result['ref_detected'] ?? false,
            'photoPath' => $photoPath,
            'refObject' => $request->ref_object,
            'refSize' => $refSize,
        ]);
    }

    /**
     * Simpan hasil ukuran (bisa diedit manual sebelum simpan)
     */
    public function store(Request $request)
    {
        $request->validate([
            'chest' => 'required|numeric|min:0',
            'waist' => 'required|numeric|min:0',
            'hips' => 'required|numeric|min:0',
            'shoulder_width' => 'required|numeric|min:0',
            'arm_length' => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',
            'photo_path' => 'nullable|string',
            'ref_object' => 'required|string',
            'is_edited' => 'nullable|boolean',
        ]);

        Measurement::create([
            'user_id' => auth()->id(),
            'chest' => $request->chest,
            'waist' => $request->waist,
            'hips' => $request->hips,
            'shoulder_width' => $request->shoulder_width,
            'arm_length' => $request->arm_length,
            'height' => $request->height,
            'photo_path' => $request->photo_path,
            'ref_object' => $request->ref_object,
            'ref_size' => $request->ref_size,
            'is_edited' => $request->boolean('is_edited', false),
        ]);

        return redirect()
            ->route('user.measurement.index')
            ->with('success', 'Data ukuran badan berhasil disimpan!');
    }

    /**
     * Hapus data ukuran
     */
    public function destroy(Measurement $measurement)
    {
        if ($measurement->user_id !== auth()->id()) {
            abort(403);
        }

        $measurement->delete();
        return back()->with('success', 'Data ukuran berhasil dihapus.');
    }
}
