<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Measurement;
use App\Services\CVMeasurementService;
use App\Services\PhotoValidationService;
use Illuminate\Http\Request;

class MeasurementController extends Controller
{
    private const REFERENCE_DIMENSIONS = [
        'aruco_a4' => [21.0, 29.7],
        'checkerboard_a4' => [21.0, 29.7],
        'a4' => [21.0, 29.7],
        'ktp' => [8.56, 5.398],
    ];

    private const MEASUREMENT_FIELDS = [
        'neck',
        'chest',
        'waist',
        'hips',
        'shoulder_width',
        'shirt_length',
        'arm_length',
        'upper_arm',
        'wrist',
        'height',
        'pants_waist',
        'pants_hips',
        'thigh',
        'knee',
        'calf',
        'ankle',
        'inseam',
        'outseam',
        'rise',
    ];

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
    public function analyze(Request $request, CVMeasurementService $cvService, PhotoValidationService $validator)
    {
        $request->validate([
            'front_photo'   => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'side_photo'    => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'back_photo'    => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'ref_object'    => 'required|in:aruco_a4,checkerboard_a4,a4,ktp,custom',
            'ref_width_cm'  => 'required_if:ref_object,custom|nullable|numeric|min:1',
            'ref_height_cm' => 'required_if:ref_object,custom|nullable|numeric|min:1',
        ]);

        [$refWidthCm, $refHeightCm] = $this->resolveReferenceDimensions(
            $request->ref_object,
            $request->ref_width_cm,
            $request->ref_height_cm,
        );

        $validations = [
            'front_photo' => $validator->validate($request->file('front_photo'), $request->ref_object, 'front'),
            'side_photo' => $validator->validate($request->file('side_photo'), $request->ref_object, 'side'),
            'back_photo' => $validator->validate($request->file('back_photo'), $request->ref_object, 'back'),
        ];

        $photoIssues = [];
        foreach ($validations as $photoName => $validation) {
            if (!$validation['valid']) {
                $label = match ($photoName) {
                    'side_photo' => 'Foto samping',
                    'back_photo' => 'Foto belakang',
                    default => 'Foto depan',
                };
                foreach ($validation['issues'] as $issue) {
                    $photoIssues[] = "{$label}: {$issue}";
                }
            }
        }

        if ($photoIssues !== []) {
            return back()
                ->withInput()
                ->with('photo_issues', $photoIssues)
                ->with('photo_suggestion', 'Gunakan marker berdiri sendiri dan ulangi foto sesuai orientasi depan, samping, dan belakang.')
                ->with('error', 'Salah satu foto tidak memenuhi protokol pengukuran otomatis.');
        }

        $folder = 'measurements/' . auth()->id();
        $frontPhotoPath = $request->file('front_photo')->store($folder, 'public');
        $sidePhotoPath = $request->file('side_photo')->store($folder, 'public');
        $backPhotoPath = $request->file('back_photo')->store($folder, 'public');

        $result = $cvService->measure(
            $request->file('front_photo'),
            $request->file('side_photo'),
            $request->file('back_photo'),
            $request->ref_object,
            $refWidthCm,
            $refHeightCm,
        );

        if (!$result['success']) {
            return back()
                ->withInput()
                ->with('error', $result['error']);
        }

        $data = $result['data'];
        $refSize = $refWidthCm && $refHeightCm ? "{$refWidthCm}x{$refHeightCm}cm" : null;

        return view('user.measurement.result', [
            'data' => $data,
            'confidence' => $result['confidence'] ?? 0,
            'qualityScore' => $result['quality_score'] ?? 0,
            'refDetected' => $result['ref_detected'] ?? false,
            'perFieldConfidence' => $result['per_field_confidence'] ?? [],
            'rawCvJson' => $result,
            'frontPhotoPath' => $frontPhotoPath,
            'sidePhotoPath' => $sidePhotoPath,
            'backPhotoPath' => $backPhotoPath,
            'refObject' => $request->ref_object,
            'refSize' => $refSize,
            'refWidthCm' => $refWidthCm,
            'refHeightCm' => $refHeightCm,
        ]);
    }

    /**
     * Simpan hasil ukuran (bisa diedit manual sebelum simpan)
     */
    public function store(Request $request)
    {
        $rules = [
            'front_photo_path' => 'nullable|string',
            'side_photo_path' => 'nullable|string',
            'back_photo_path' => 'nullable|string',
            'ref_object' => 'required|string',
            'ref_size' => 'nullable|string',
            'ref_width_cm' => 'nullable|numeric|min:0',
            'ref_height_cm' => 'nullable|numeric|min:0',
            'confidence_score' => 'nullable|numeric|min:0|max:1',
            'quality_score' => 'nullable|numeric|min:0|max:1',
            'raw_cv_json' => 'nullable|string',
            'is_edited' => 'nullable|boolean',
        ];

        foreach (self::MEASUREMENT_FIELDS as $field) {
            $rules[$field] = 'nullable|numeric|min:0|max:400';
            $rules["original_{$field}"] = 'nullable|numeric|min:0|max:400';
        }

        $validated = $request->validate($rules);
        $editedFields = [];
        foreach (self::MEASUREMENT_FIELDS as $field) {
            $current = (float) ($validated[$field] ?? 0);
            $original = (float) ($validated["original_{$field}"] ?? $current);
            if (abs($current - $original) >= 0.01) {
                $editedFields[$field] = [
                    'original' => $original,
                    'current' => $current,
                ];
            }
        }

        $rawCv = null;
        if (!empty($validated['raw_cv_json'])) {
            $decoded = json_decode($validated['raw_cv_json'], true);
            $rawCv = is_array($decoded) ? $decoded : null;
        }

        $payload = [
            'user_id' => auth()->id(),
            'photo_path' => $validated['front_photo_path'] ?? null,
            'front_photo_path' => $validated['front_photo_path'] ?? null,
            'side_photo_path' => $validated['side_photo_path'] ?? null,
            'back_photo_path' => $validated['back_photo_path'] ?? null,
            'ref_object' => $validated['ref_object'],
            'ref_size' => $validated['ref_size'] ?? null,
            'ref_width_cm' => $validated['ref_width_cm'] ?? null,
            'ref_height_cm' => $validated['ref_height_cm'] ?? null,
            'measurement_method' => 'multiview_cv',
            'confidence_score' => $validated['confidence_score'] ?? null,
            'quality_score' => $validated['quality_score'] ?? null,
            'raw_cv_json' => $rawCv,
            'edited_fields_json' => $editedFields,
            'is_edited' => $request->boolean('is_edited', false) || $editedFields !== [],
        ];

        foreach (self::MEASUREMENT_FIELDS as $field) {
            $payload[$field] = $validated[$field] ?? null;
        }

        Measurement::create($payload);

        return redirect()
            ->route('user.measurement.index')
            ->with('success', 'Data ukuran badan berhasil disimpan!');
    }

    /**
     * Hapus data ukuran
     */
    public function destroy(Measurement $measurement)
    {
        if ((int) $measurement->user_id !== (int) auth()->id()) {
            abort(403);
        }

        $measurement->delete();
        return back()->with('success', 'Data ukuran berhasil dihapus.');
    }

    private function resolveReferenceDimensions(string $refObject, mixed $width, mixed $height): array
    {
        if (isset(self::REFERENCE_DIMENSIONS[$refObject])) {
            return self::REFERENCE_DIMENSIONS[$refObject];
        }

        return [
            $width !== null ? (float) $width : null,
            $height !== null ? (float) $height : null,
        ];
    }
}
