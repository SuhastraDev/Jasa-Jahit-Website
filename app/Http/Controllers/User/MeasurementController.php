<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Measurement;
use App\Services\CVMeasurementService;
use App\Services\PhotoValidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MeasurementController extends Controller
{
    private const REFERENCE_DIMENSIONS = [
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

    private const BODYM_FIELDS = [
        'ankle_girth',
        'arm_length',
        'bicep_girth',
        'calf_girth',
        'chest_girth',
        'forearm_girth',
        'height',
        'hip_girth',
        'leg_length',
        'shoulder_breadth',
        'shoulder_to_crotch',
        'thigh_girth',
        'waist_girth',
        'wrist_girth',
    ];

    private const BODYM_TO_LEGACY_FIELDS = [
        'ankle_girth' => 'ankle',
        'arm_length' => 'arm_length',
        'bicep_girth' => 'upper_arm',
        'calf_girth' => 'calf',
        'chest_girth' => 'chest',
        'height' => 'height',
        'hip_girth' => 'hips',
        'shoulder_breadth' => 'shoulder_width',
        'thigh_girth' => 'thigh',
        'waist_girth' => 'waist',
        'wrist_girth' => 'wrist',
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

    public function poseModel()
    {
        $path = base_path('python-cv/pose_landmarker_lite.task');
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'public, max-age=604800, immutable',
        ]);
    }

    public function mediaPipeAsset(string $file)
    {
        $allowed = [
            'vision_wasm_internal.js' => 'application/javascript',
            'vision_wasm_internal.wasm' => 'application/wasm',
            'vision_wasm_nosimd_internal.js' => 'application/javascript',
            'vision_wasm_nosimd_internal.wasm' => 'application/wasm',
        ];
        abort_unless(isset($allowed[$file]), 404);

        $path = base_path("node_modules/@mediapipe/tasks-vision/wasm/{$file}");
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => $allowed[$file],
            'Cache-Control' => 'public, max-age=604800, immutable',
        ]);
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
            'ref_object'    => 'required|in:a4,ktp',
            'reference_mode' => 'required|in:fixed,handheld',
            'ref_width_cm'  => 'nullable|numeric|min:1',
            'ref_height_cm' => 'nullable|numeric|min:1',
            'front_reference_box' => 'nullable|string',
            'side_reference_box' => 'nullable|string',
            'back_reference_box' => 'nullable|string',
        ], [
            'front_photo.max' => 'Foto depan terlalu besar. Maksimal 5MB per foto.',
            'side_photo.max' => 'Foto samping terlalu besar. Maksimal 5MB per foto.',
            'back_photo.max' => 'Foto belakang terlalu besar. Maksimal 5MB per foto.',
            'front_photo.image' => 'Foto depan harus berupa file gambar.',
            'side_photo.image' => 'Foto samping harus berupa file gambar.',
            'back_photo.image' => 'Foto belakang harus berupa file gambar.',
            'front_photo.mimes' => 'Foto depan harus berformat JPG, JPEG, PNG, atau WEBP.',
            'side_photo.mimes' => 'Foto samping harus berformat JPG, JPEG, PNG, atau WEBP.',
            'back_photo.mimes' => 'Foto belakang harus berformat JPG, JPEG, PNG, atau WEBP.',
        ]);

        if ($request->ref_object === 'ktp' && $request->reference_mode === 'handheld') {
            return back()
                ->withInput()
                ->withErrors(['reference_mode' => 'Mode praktis hanya tersedia untuk A4. KTP harus ditempel atau disandarkan.']);
        }

        [$refWidthCm, $refHeightCm] = $this->resolveReferenceDimensions(
            $request->ref_object,
            $request->ref_width_cm,
            $request->ref_height_cm,
        );

        $validations = $validator->validateMany([
            'front_photo' => [
                'photo' => $request->file('front_photo'),
                'orientation' => 'front',
            ],
            'side_photo' => [
                'photo' => $request->file('side_photo'),
                'orientation' => 'side',
            ],
            'back_photo' => [
                'photo' => $request->file('back_photo'),
                'orientation' => 'back',
            ],
        ], $request->ref_object, $request->reference_mode);

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
                ->with('photo_suggestion', 'Gunakan A4 atau KTP sebagai benda patokan ukuran, lalu ulangi foto sesuai orientasi depan, samping, dan belakang.')
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
            $request->reference_mode,
            [
                'front' => $request->input('front_reference_box'),
                'side' => $request->input('side_reference_box'),
                'back' => $request->input('back_reference_box'),
            ],
        );

        if (!$result['success']) {
            return back()
                ->withInput()
                ->with('error', $result['error']);
        }

        $confidence = (float) ($result['confidence'] ?? 0);
        $qualityScore = (float) ($result['quality_score'] ?? 0);
        $perFieldConfidence = $result['per_field_confidence'] ?? [];
        if ($request->reference_mode === 'handheld') {
            $confidence = max(0, round($confidence * 0.9, 4));
            $qualityScore = max(0, round($qualityScore * 0.95, 4));
            foreach ($perFieldConfidence as $field => $fieldConfidence) {
                $perFieldConfidence[$field] = max(0, round((float) $fieldConfidence * 0.9, 4));
            }
            $result['confidence'] = $confidence;
            $result['quality_score'] = $qualityScore;
            $result['per_field_confidence'] = $perFieldConfidence;
            $result['reference_mode_adjustment'] = 'Confidence dikurangi untuk mode praktis karena A4 dipegang tangan.';
        }

        $data = $this->legacyDataFromResult($result);
        $bodymData = $this->bodymDataFromResult($result);
        $refSize = $refWidthCm && $refHeightCm ? "{$refWidthCm}x{$refHeightCm}cm" : null;

        return view('user.measurement.result', [
            'data' => $data,
            'bodymData' => $bodymData,
            'bodymMetadata' => $this->bodymMetadataFromResult($result),
            'confidence' => $confidence,
            'qualityScore' => $qualityScore,
            'refDetected' => $result['ref_detected'] ?? false,
            'perFieldConfidence' => $perFieldConfidence,
            'rawCvJson' => $result,
            'frontPhotoPath' => $frontPhotoPath,
            'sidePhotoPath' => $sidePhotoPath,
            'backPhotoPath' => $backPhotoPath,
            'refObject' => $request->ref_object,
            'refSize' => $refSize,
            'refWidthCm' => $refWidthCm,
            'refHeightCm' => $refHeightCm,
            'referenceMode' => $request->reference_mode,
        ]);
    }

    public function startAnalysis(
        Request $request,
        CVMeasurementService $cvService,
        PhotoValidationService $validator
    ) {
        $request->validate($this->analysisRules(), $this->analysisMessages());

        if ($request->ref_object === 'ktp' && $request->reference_mode === 'handheld') {
            return response()->json([
                'message' => 'Mode praktis hanya tersedia untuk A4. KTP harus ditempel atau disandarkan.',
                'errors' => ['reference_mode' => ['Mode praktis hanya tersedia untuk A4.']],
            ], 422);
        }

        [$refWidthCm, $refHeightCm] = $this->resolveReferenceDimensions(
            $request->ref_object,
            $request->ref_width_cm,
            $request->ref_height_cm,
        );

        $validations = $validator->validateMany([
            'front_photo' => ['photo' => $request->file('front_photo'), 'orientation' => 'front'],
            'side_photo' => ['photo' => $request->file('side_photo'), 'orientation' => 'side'],
            'back_photo' => ['photo' => $request->file('back_photo'), 'orientation' => 'back'],
        ], $request->ref_object, $request->reference_mode);

        $photoIssues = $this->collectPhotoIssues($validations);
        if ($photoIssues !== []) {
            return response()->json([
                'message' => 'Salah satu foto tidak memenuhi protokol pengukuran.',
                'photo_issues' => $photoIssues,
            ], 422);
        }

        $job = $cvService->startMeasurementJob(
            $request->file('front_photo'),
            $request->file('side_photo'),
            $request->file('back_photo'),
            $request->ref_object,
            $refWidthCm,
            $refHeightCm,
            $request->reference_mode,
            [
                'front' => $request->input('front_reference_box'),
                'side' => $request->input('side_reference_box'),
                'back' => $request->input('back_reference_box'),
            ],
        );

        if (!($job['success'] ?? false) || empty($job['job_id'])) {
            return response()->json([
                'message' => $job['error'] ?? 'Analisis tidak dapat dimulai.',
            ], 503);
        }

        $folder = 'measurements/' . auth()->id();
        $context = [
            'user_id' => (int) auth()->id(),
            'front_photo_path' => $request->file('front_photo')->store($folder, 'public'),
            'side_photo_path' => $request->file('side_photo')->store($folder, 'public'),
            'back_photo_path' => $request->file('back_photo')->store($folder, 'public'),
            'ref_object' => $request->ref_object,
            'ref_width_cm' => $refWidthCm,
            'ref_height_cm' => $refHeightCm,
            'reference_mode' => $request->reference_mode,
            'result' => null,
        ];
        Cache::put($this->analysisCacheKey($job['job_id']), $context, now()->addMinutes(45));

        return response()->json([
            'success' => true,
            'job_id' => $job['job_id'],
            'status_url' => route('user.measurement.analysis-status', $job['job_id']),
        ], 202);
    }

    public function analysisStatus(string $jobId, CVMeasurementService $cvService)
    {
        $context = Cache::get($this->analysisCacheKey($jobId));
        abort_unless($context && (int) $context['user_id'] === (int) auth()->id(), 404);

        $job = $cvService->measurementJobStatus($jobId);
        if (($job['status'] ?? null) === 'completed' && !empty($job['result'])) {
            $context['result'] = $this->adjustResultForReferenceMode(
                $job['result'],
                $context['reference_mode'],
            );
            Cache::put($this->analysisCacheKey($jobId), $context, now()->addMinutes(45));
            $job['result_url'] = route('user.measurement.analysis-result', $jobId);
            unset($job['result']);
        }

        return response()->json($job);
    }

    public function analysisResult(string $jobId)
    {
        $context = Cache::get($this->analysisCacheKey($jobId));
        abort_unless(
            $context
                && (int) $context['user_id'] === (int) auth()->id()
                && is_array($context['result']),
            404,
        );

        return view('user.measurement.result', $this->resultViewData($context['result'], $context));
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
            'reference_mode' => 'nullable|in:fixed,handheld',
            'confidence_score' => 'nullable|numeric|min:0|max:1',
            'quality_score' => 'nullable|numeric|min:0|max:1',
            'raw_cv_json' => 'nullable|string',
            'bodym_data_json' => 'nullable|string',
            'bodym_per_field_confidence_json' => 'nullable|string',
            'bodym_prediction_intervals_cm_json' => 'nullable|string',
            'bodym_diagnostics_json' => 'nullable|string',
            'bodym_contract_version' => 'nullable|string|max:40',
            'bodym_response_contract_version' => 'nullable|string|max:40',
            'bodym_model_version' => 'nullable|string|max:80',
            'bodym_status' => 'nullable|string|max:40',
            'is_edited' => 'nullable|boolean',
        ];

        foreach (self::MEASUREMENT_FIELDS as $field) {
            $rules[$field] = 'nullable|numeric|min:0|max:400';
            $rules["original_{$field}"] = 'nullable|numeric|min:0|max:400';
        }

        foreach (self::BODYM_FIELDS as $field) {
            $rules["bodym_{$field}"] = 'nullable|numeric|min:0|max:400';
            $rules["original_bodym_{$field}"] = 'nullable|numeric|min:0|max:400';
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

        foreach (self::BODYM_FIELDS as $field) {
            $key = "bodym_{$field}";
            $current = (float) ($validated[$key] ?? 0);
            $original = (float) ($validated["original_{$key}"] ?? $current);
            if (abs($current - $original) >= 0.01) {
                $editedFields[$key] = [
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

        $bodymData = $this->decodeJsonInput($validated['bodym_data_json'] ?? null);
        $bodymConfidence = $this->decodeJsonInput($validated['bodym_per_field_confidence_json'] ?? null);
        $bodymIntervals = $this->decodeJsonInput($validated['bodym_prediction_intervals_cm_json'] ?? null);
        $bodymDiagnostics = $this->decodeJsonInput($validated['bodym_diagnostics_json'] ?? null);

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
            'reference_mode' => $validated['reference_mode'] ?? 'fixed',
            'measurement_method' => ($validated['bodym_status'] ?? null) === 'ok' ? 'bodym_ml' : 'multiview_cv',
            'bodym_contract_version' => $validated['bodym_contract_version'] ?? null,
            'bodym_response_contract_version' => $validated['bodym_response_contract_version'] ?? null,
            'bodym_model_version' => $validated['bodym_model_version'] ?? null,
            'bodym_status' => $validated['bodym_status'] ?? null,
            'confidence_score' => $validated['confidence_score'] ?? null,
            'quality_score' => $validated['quality_score'] ?? null,
            'raw_cv_json' => $rawCv,
            'bodym_data' => $bodymData,
            'bodym_per_field_confidence' => $bodymConfidence,
            'bodym_prediction_intervals_cm' => $bodymIntervals,
            'bodym_diagnostics' => $bodymDiagnostics,
            'edited_fields_json' => $editedFields,
            'is_edited' => $request->boolean('is_edited', false) || $editedFields !== [],
        ];

        foreach (self::MEASUREMENT_FIELDS as $field) {
            $payload[$field] = $validated[$field] ?? null;
        }

        foreach (self::BODYM_FIELDS as $field) {
            $payload["bodym_{$field}"] = $validated["bodym_{$field}"] ?? ($bodymData[$field] ?? null);
        }

        foreach (self::BODYM_TO_LEGACY_FIELDS as $bodymField => $legacyField) {
            $bodymKey = "bodym_{$bodymField}";
            if (array_key_exists($bodymKey, $validated) && $validated[$bodymKey] !== null) {
                $payload[$legacyField] = $validated[$bodymKey];
            }
        }

        if (array_key_exists('bodym_waist_girth', $validated) && $validated['bodym_waist_girth'] !== null) {
            $payload['pants_waist'] = $validated['bodym_waist_girth'];
        }

        if (array_key_exists('bodym_hip_girth', $validated) && $validated['bodym_hip_girth'] !== null) {
            $payload['pants_hips'] = $validated['bodym_hip_girth'];
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

    private function analysisRules(): array
    {
        return [
            'front_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'side_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'back_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'ref_object' => 'required|in:a4,ktp',
            'reference_mode' => 'required|in:fixed,handheld',
            'ref_width_cm' => 'nullable|numeric|min:1',
            'ref_height_cm' => 'nullable|numeric|min:1',
            'front_reference_box' => 'nullable|string',
            'side_reference_box' => 'nullable|string',
            'back_reference_box' => 'nullable|string',
        ];
    }

    private function analysisMessages(): array
    {
        return [
            'front_photo.max' => 'Foto depan terlalu besar. Maksimal 5MB per foto.',
            'side_photo.max' => 'Foto samping terlalu besar. Maksimal 5MB per foto.',
            'back_photo.max' => 'Foto belakang terlalu besar. Maksimal 5MB per foto.',
            'front_photo.image' => 'Foto depan harus berupa file gambar.',
            'side_photo.image' => 'Foto samping harus berupa file gambar.',
            'back_photo.image' => 'Foto belakang harus berupa file gambar.',
            'front_photo.mimes' => 'Foto depan harus berformat JPG, JPEG, PNG, atau WEBP.',
            'side_photo.mimes' => 'Foto samping harus berformat JPG, JPEG, PNG, atau WEBP.',
            'back_photo.mimes' => 'Foto belakang harus berformat JPG, JPEG, PNG, atau WEBP.',
        ];
    }

    private function collectPhotoIssues(array $validations): array
    {
        $issues = [];
        foreach ($validations as $photoName => $validation) {
            if ($validation['valid']) {
                continue;
            }
            $label = match ($photoName) {
                'side_photo' => 'Foto samping',
                'back_photo' => 'Foto belakang',
                default => 'Foto depan',
            };
            foreach ($validation['issues'] as $issue) {
                $issues[] = "{$label}: {$issue}";
            }
        }

        return $issues;
    }

    private function adjustResultForReferenceMode(array $result, string $referenceMode): array
    {
        if ($referenceMode !== 'handheld') {
            return $result;
        }

        $result['confidence'] = max(0, round((float) ($result['confidence'] ?? 0) * 0.9, 4));
        $result['quality_score'] = max(0, round((float) ($result['quality_score'] ?? 0) * 0.95, 4));
        foreach (($result['per_field_confidence'] ?? []) as $field => $confidence) {
            $result['per_field_confidence'][$field] = max(0, round((float) $confidence * 0.9, 4));
        }
        $result['reference_mode_adjustment'] = 'Confidence dikurangi untuk mode praktis karena A4 dipegang tangan.';

        return $result;
    }

    private function resultViewData(array $result, array $context): array
    {
        $refWidthCm = $context['ref_width_cm'];
        $refHeightCm = $context['ref_height_cm'];

        return [
            'data' => $this->legacyDataFromResult($result),
            'bodymData' => $this->bodymDataFromResult($result),
            'bodymMetadata' => $this->bodymMetadataFromResult($result),
            'confidence' => (float) ($result['confidence'] ?? 0),
            'qualityScore' => (float) ($result['quality_score'] ?? 0),
            'refDetected' => $result['ref_detected'] ?? false,
            'perFieldConfidence' => $result['per_field_confidence'] ?? [],
            'rawCvJson' => $result,
            'frontPhotoPath' => $context['front_photo_path'],
            'sidePhotoPath' => $context['side_photo_path'],
            'backPhotoPath' => $context['back_photo_path'],
            'refObject' => $context['ref_object'],
            'refSize' => $refWidthCm && $refHeightCm ? "{$refWidthCm}x{$refHeightCm}cm" : null,
            'refWidthCm' => $refWidthCm,
            'refHeightCm' => $refHeightCm,
            'referenceMode' => $context['reference_mode'],
        ];
    }

    private function analysisCacheKey(string $jobId): string
    {
        return 'measurement_analysis:' . auth()->id() . ':' . $jobId;
    }

    private function bodymDataFromResult(array $result): array
    {
        $data = is_array($result['bodym_data'] ?? null) ? $result['bodym_data'] : [];
        if ($data === [] && ($result['measurement_method'] ?? null) === 'bodym_ml') {
            $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        }

        $bodymData = [];
        foreach (self::BODYM_FIELDS as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                $bodymData[$field] = round((float) $data[$field], 2);
            }
        }

        return $bodymData;
    }

    private function legacyDataFromResult(array $result): array
    {
        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $bodymData = $this->bodymDataFromResult($result);

        foreach (self::BODYM_TO_LEGACY_FIELDS as $bodymField => $legacyField) {
            if (isset($bodymData[$bodymField])) {
                $data[$legacyField] = $bodymData[$bodymField];
            }
        }

        if (isset($bodymData['waist_girth'])) {
            $data['pants_waist'] = $bodymData['waist_girth'];
        }
        if (isset($bodymData['hip_girth'])) {
            $data['pants_hips'] = $bodymData['hip_girth'];
        }
        return $data;
    }

    private function bodymMetadataFromResult(array $result): array
    {
        return [
            'contract_version' => $result['bodym_contract_version']
                ?? $result['contract_version']
                ?? config('bodym.contract_version'),
            'response_contract_version' => $result['response_contract_version']
                ?? config('bodym.response_contract_version'),
            'model_version' => $result['bodym_model_version']
                ?? $result['model_version']
                ?? config('bodym.model_version'),
            'status' => $result['bodym_status']
                ?? (($result['measurement_method'] ?? null) === 'bodym_ml' ? 'ok' : null),
            'per_field_confidence' => $result['bodym_per_field_confidence']
                ?? $result['per_field_confidence']
                ?? [],
            'prediction_intervals_cm' => $result['bodym_prediction_intervals_cm']
                ?? $result['prediction_intervals_cm']
                ?? [],
            'diagnostics' => [
                'photo_diagnostics' => $result['photo_diagnostics'] ?? null,
                'model_diagnostics' => $result['bodym_diagnostics'] ?? null,
                'legacy_fallback_fields' => $result['legacy_fallback_fields'] ?? [],
            ],
        ];
    }

    private function decodeJsonInput(?string $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}
