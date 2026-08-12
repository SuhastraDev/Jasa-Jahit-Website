@extends('layouts.user')
@section('page-title', 'Hasil Analisis Ukuran')
@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6">
        <a href="{{ route('user.measurement.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 font-medium">Kembali ke Ukur Badan</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Hasil Analisis Multi-view</h1>
        <p class="text-gray-500 text-sm mt-1">Periksa nilai estimasi sebelum digunakan untuk pola jahit.</p>
    </div>

    @php
        $confidencePct = round(($confidence ?? 0) * 100);
        $qualityPct = round(($qualityScore ?? 0) * 100);
        $isGood = ($confidence ?? 0) >= 0.7 && ($qualityScore ?? 0) >= 0.7 && $refDetected;
        $topFields = [
            ['Lingkar Leher', 'neck', 'lingkar', 'Mengelilingi pangkal leher'],
            ['Lingkar Dada', 'chest', 'lingkar', 'Mengelilingi bagian dada terlebar'],
            ['Lingkar Pinggang', 'waist', 'lingkar', 'Mengelilingi pinggang badan'],
            ['Lingkar Pinggul', 'hips', 'lingkar', 'Mengelilingi bagian pinggul terlebar'],
            ['Lebar Bahu', 'shoulder_width', 'lebar', 'Jarak lurus bahu kiri ke bahu kanan'],
            ['Panjang Baju', 'shirt_length', 'panjang', 'Jarak vertikal bahu ke bawah baju'],
            ['Panjang Lengan', 'arm_length', 'panjang', 'Jarak bahu ke pergelangan tangan'],
            ['Lingkar Lengan Atas', 'upper_arm', 'lingkar', 'Mengelilingi bagian lengan atas'],
            ['Lingkar Pergelangan', 'wrist', 'lingkar', 'Mengelilingi pergelangan tangan'],
            ['Tinggi Badan', 'height', 'panjang', 'Jarak vertikal kepala sampai telapak kaki'],
        ];
        $pantsFields = [
            ['Lingkar Pinggang Celana', 'pants_waist', 'lingkar', 'Mengelilingi posisi ban pinggang celana'],
            ['Lingkar Pinggul Celana', 'pants_hips', 'lingkar', 'Mengelilingi bagian pinggul terlebar'],
            ['Lingkar Paha', 'thigh', 'lingkar', 'Mengelilingi bagian paha terlebar'],
            ['Lingkar Lutut', 'knee', 'lingkar', 'Mengelilingi area lutut'],
            ['Lingkar Betis', 'calf', 'lingkar', 'Mengelilingi bagian betis terlebar'],
            ['Lingkar Bukaan Bawah', 'ankle', 'lingkar', 'Mengelilingi bukaan celana di pergelangan kaki'],
            ['Panjang Inseam', 'inseam', 'panjang', 'Jarak selangkangan ke pergelangan kaki bagian dalam'],
            ['Panjang Outseam', 'outseam', 'panjang', 'Jarak pinggang ke pergelangan kaki bagian luar'],
            ['Panjang Pesak', 'rise', 'panjang', 'Jarak vertikal pinggang ke selangkangan'],
        ];
        $bodymFields = collect(config('bodym.measurements'))
            ->map(fn ($meta, $name) => [
                $meta['label'],
                $name,
                match ($meta['type']) {
                    'circumference' => 'lingkar',
                    'breadth' => 'lebar',
                    'height' => 'tinggi',
                    default => 'panjang',
                },
                'Indikator estimasi: ' . str_replace('_', ' ', $name),
            ])
            ->values()
            ->all();
        $bodymPerFieldConfidence = $bodymMetadata['per_field_confidence'] ?? [];
        $bodymPredictionIntervals = $bodymMetadata['prediction_intervals_cm'] ?? [];
        $normalizeInterval = function ($interval) {
            if (!is_array($interval)) {
                return null;
            }

            $values = array_values(array_filter($interval, 'is_numeric'));
            if (count($values) < 2) {
                return null;
            }

            return [round((float) $values[0], 1), round((float) $values[1], 1)];
        };
        $typeStyles = [
            'lingkar' => 'border-violet-200 bg-violet-50 text-violet-700',
            'lebar' => 'border-cyan-200 bg-cyan-50 text-cyan-700',
            'panjang' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'tinggi' => 'border-amber-200 bg-amber-50 text-amber-700',
        ];
        $hasBodymResult = !empty($bodymData);
        $photoDiagnostics = $photoDiagnostics ?? ($rawCvJson['photo_diagnostics'] ?? []);
        $photoSources = $photoSources ?? ['front' => 'upload', 'side' => 'upload', 'back' => 'upload'];
        $photoViews = [
            'front' => 'Foto Depan',
            'side' => 'Foto Samping',
            'back' => 'Foto Belakang',
        ];
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-xl border p-4 {{ $isGood ? 'bg-green-50 border-green-200' : 'bg-amber-50 border-amber-200' }}">
            <p class="text-xs font-semibold {{ $isGood ? 'text-green-700' : 'text-amber-700' }}">Confidence</p>
            <p class="text-2xl font-black {{ $isGood ? 'text-green-800' : 'text-amber-800' }}">{{ $confidencePct }}%</p>
        </div>
        <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
            <p class="text-xs font-semibold text-blue-700">Quality Score</p>
            <p class="text-2xl font-black text-blue-800">{{ $qualityPct }}%</p>
        </div>
        <div class="rounded-xl border {{ $refDetected ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }} p-4">
            <p class="text-xs font-semibold {{ $refDetected ? 'text-green-700' : 'text-red-700' }}">Benda Patokan</p>
            <p class="text-sm font-bold {{ $refDetected ? 'text-green-800' : 'text-red-800' }}">{{ $refDetected ? 'Terdeteksi pada semua foto' : 'Tidak lengkap' }}</p>
        </div>
        <div class="rounded-xl border {{ ($referenceMode ?? 'fixed') === 'handheld' ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-slate-50' }} p-4">
            <p class="text-xs font-semibold {{ ($referenceMode ?? 'fixed') === 'handheld' ? 'text-amber-700' : 'text-slate-600' }}">Mode Patokan</p>
            <p class="text-sm font-bold {{ ($referenceMode ?? 'fixed') === 'handheld' ? 'text-amber-800' : 'text-slate-800' }}">{{ ($referenceMode ?? 'fixed') === 'handheld' ? 'Praktis - A4 dipegang' : 'Akurat - ditempel/disandarkan' }}</p>
            @if(($referenceMode ?? 'fixed') === 'handheld')
            <p class="text-[11px] text-amber-700 mt-1">Confidence dikurangi karena posisi tangan dapat memengaruhi siluet.</p>
            @endif
        </div>
    </div>

    @if(!empty($photoDiagnostics))
    <section class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-bold text-slate-900">Pemeriksaan Tiga Foto</h2>
            <p class="mt-1 text-xs leading-relaxed text-slate-500">Setiap foto diperiksa terpisah sebelum ukuran depan dan kedalaman samping digabungkan.</p>
        </div>
        <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-3">
            @foreach($photoViews as $view => $label)
            @php
                $diagnostic = $photoDiagnostics[$view] ?? [];
                $processing = $diagnostic['reference_processing'] ?? [];
                $perspectiveCorrected = (bool) ($processing['perspective_rectified'] ?? false);
                $referenceQuality = round(((float) ($diagnostic['reference_quality'] ?? 0)) * 100);
                $sourceLabel = ($photoSources[$view] ?? 'upload') === 'camera' ? 'Kamera langsung' : 'File upload';
            @endphp
            <article class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                <div class="flex items-start justify-between gap-2">
                    <h3 class="text-xs font-black text-slate-800">{{ $label }}</h3>
                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[9px] font-black uppercase text-blue-700">{{ $sourceLabel }}</span>
                </div>
                <div class="mt-3 space-y-2 text-[11px]">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Kalibrasi bidang</span>
                        <span class="font-bold {{ $perspectiveCorrected ? 'text-green-700' : 'text-amber-700' }}">{{ $perspectiveCorrected ? 'Perspektif dikoreksi' : 'Skala alternatif' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Siluet tubuh</span>
                        <span class="font-bold text-green-700">{{ !empty($diagnostic['pose_fallback']) ? 'Terbaca alternatif' : 'Terbaca' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Kualitas patokan</span>
                        <span class="font-bold text-slate-700">{{ $referenceQuality }}%</span>
                    </div>
                </div>
            </article>
            @endforeach
        </div>
    </section>
    @endif

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden" x-data="{ edited: false }">
        <div class="px-6 py-5 border-b border-gray-50">
            <h2 class="font-bold text-gray-900">Estimasi Ukuran Badan</h2>
            <p class="text-sm text-gray-500 mt-0.5">Setiap nilai diberi jenis ukuran agar jelas cara meteran digunakan. Field dapat diedit sebelum disimpan.</p>
            <div class="mt-4 flex flex-wrap items-center gap-2" aria-label="Jenis ukuran">
                <span class="mr-1 text-xs font-bold text-slate-500">Jenis ukuran</span>
                <span class="inline-flex items-center rounded-md border border-violet-200 bg-violet-50 px-2 py-1 text-[10px] font-black text-violet-700">LINGKAR</span>
                <span class="text-xs text-slate-500">mengelilingi tubuh</span>
                <span class="inline-flex items-center rounded-md border border-cyan-200 bg-cyan-50 px-2 py-1 text-[10px] font-black text-cyan-700">LEBAR</span>
                <span class="text-xs text-slate-500">jarak mendatar</span>
                <span class="inline-flex items-center rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 text-[10px] font-black text-emerald-700">PANJANG</span>
                <span class="text-xs text-slate-500">jarak memanjang/vertikal</span>
                <span class="inline-flex items-center rounded-md border border-amber-200 bg-amber-50 px-2 py-1 text-[10px] font-black text-amber-700">TINGGI</span>
                <span class="text-xs text-slate-500">tinggi badan</span>
            </div>
        </div>

        <form action="{{ route('user.measurement.store') }}" method="POST" class="p-6">
            @csrf
            <input type="hidden" name="front_photo_path" value="{{ $frontPhotoPath }}">
            <input type="hidden" name="side_photo_path" value="{{ $sidePhotoPath }}">
            <input type="hidden" name="back_photo_path" value="{{ $backPhotoPath }}">
            <input type="hidden" name="ref_object" value="{{ $refObject }}">
            <input type="hidden" name="ref_size" value="{{ $refSize }}">
            <input type="hidden" name="ref_width_cm" value="{{ $refWidthCm }}">
            <input type="hidden" name="ref_height_cm" value="{{ $refHeightCm }}">
            <input type="hidden" name="reference_mode" value="{{ $referenceMode ?? 'fixed' }}">
            <input type="hidden" name="confidence_score" value="{{ $confidence }}">
            <input type="hidden" name="quality_score" value="{{ $qualityScore }}">
            <input type="hidden" name="raw_cv_json" value='@json($rawCvJson)'>
            <input type="hidden" name="bodym_data_json" value='@json($bodymData ?? [])'>
            <input type="hidden" name="bodym_per_field_confidence_json" value='@json($bodymPerFieldConfidence)'>
            <input type="hidden" name="bodym_prediction_intervals_cm_json" value='@json($bodymPredictionIntervals)'>
            <input type="hidden" name="bodym_diagnostics_json" value='@json($bodymMetadata["diagnostics"] ?? [])'>
            <input type="hidden" name="bodym_contract_version" value="{{ $bodymMetadata['contract_version'] ?? config('bodym.contract_version') }}">
            <input type="hidden" name="bodym_response_contract_version" value="{{ $bodymMetadata['response_contract_version'] ?? config('bodym.response_contract_version') }}">
            <input type="hidden" name="bodym_model_version" value="{{ $bodymMetadata['model_version'] ?? config('bodym.model_version') }}">
            <input type="hidden" name="bodym_status" value="{{ $bodymMetadata['status'] ?? '' }}">
            <input type="hidden" name="is_edited" x-bind:value="edited ? 1 : 0">

            @if($hasBodymResult)
                @foreach(array_merge($topFields, $pantsFields) as [$label, $name])
                <input type="hidden" name="{{ $name }}" value="{{ $data[$name] ?? '' }}">
                <input type="hidden" name="original_{{ $name }}" value="{{ $data[$name] ?? '' }}">
                @endforeach
            @else
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <section>
                    <h3 class="text-sm font-bold text-gray-900 mb-3">Ukuran Baju</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($topFields as [$label, $name, $type, $description])
                        @php
                            $value = $data[$name] ?? 0;
                            $fieldConfidence = $perFieldConfidence[$name] ?? null;
                        @endphp
                        <div class="bg-gray-50 rounded-xl p-4">
                            <div class="mb-1.5 flex items-start justify-between gap-2">
                                <label class="block text-xs font-bold text-slate-700">{{ $label }}</label>
                                <span class="shrink-0 rounded-md border px-2 py-0.5 text-[9px] font-black uppercase {{ $typeStyles[$type] }}">{{ $type }}</span>
                            </div>
                            <p class="mb-2 min-h-8 text-[11px] leading-4 text-slate-500">{{ $description }}</p>
                            <input type="hidden" name="original_{{ $name }}" value="{{ $value }}">
                            <div class="flex items-center gap-2">
                                <input type="number" step="0.01" name="{{ $name }}" value="{{ $value }}" @input="edited = true"
                                    class="flex-1 bg-white rounded-lg border-gray-200 text-base font-bold focus:border-blue-500 focus:ring-blue-500">
                                <span class="text-sm text-gray-400 font-medium">cm</span>
                            </div>
                            @if($fieldConfidence !== null)
                            <p class="text-[11px] text-blue-600 mt-1">Confidence {{ round($fieldConfidence * 100) }}%</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </section>

                <section>
                    <h3 class="text-sm font-bold text-gray-900 mb-3">Ukuran Celana</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($pantsFields as [$label, $name, $type, $description])
                        @php
                            $value = $data[$name] ?? 0;
                            $fieldConfidence = $perFieldConfidence[$name] ?? null;
                        @endphp
                        <div class="bg-gray-50 rounded-xl p-4">
                            <div class="mb-1.5 flex items-start justify-between gap-2">
                                <label class="block text-xs font-bold text-slate-700">{{ $label }}</label>
                                <span class="shrink-0 rounded-md border px-2 py-0.5 text-[9px] font-black uppercase {{ $typeStyles[$type] }}">{{ $type }}</span>
                            </div>
                            <p class="mb-2 min-h-8 text-[11px] leading-4 text-slate-500">{{ $description }}</p>
                            <input type="hidden" name="original_{{ $name }}" value="{{ $value }}">
                            <div class="flex items-center gap-2">
                                <input type="number" step="0.01" name="{{ $name }}" value="{{ $value }}" @input="edited = true"
                                    class="flex-1 bg-white rounded-lg border-gray-200 text-base font-bold focus:border-blue-500 focus:ring-blue-500">
                                <span class="text-sm text-gray-400 font-medium">cm</span>
                            </div>
                            @if($fieldConfidence !== null)
                            <p class="text-[11px] text-blue-600 mt-1">Confidence {{ round($fieldConfidence * 100) }}%</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </section>
            </div>
            @endif

            @if($hasBodymResult)
            <section class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Indikator Ukuran Tubuh</h3>
                        <p class="text-xs text-slate-500">Form ini menampilkan 14 indikator utama. Mapping ke kebutuhan jahit tetap disimpan di belakang layar untuk kompatibilitas pesanan.</p>
                    </div>
                    <div class="text-[11px] font-bold text-slate-500">Model estimasi aktif</div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($bodymFields as [$label, $name, $type, $description])
                    @continue(!array_key_exists($name, $bodymData))
                    @php
                        $value = $bodymData[$name];
                        $fieldConfidence = $bodymPerFieldConfidence[$name] ?? null;
                        $interval = $normalizeInterval($bodymPredictionIntervals[$name] ?? null);
                    @endphp
                    <div class="rounded-xl border border-white bg-white p-4 shadow-sm">
                        <div class="mb-1.5 flex items-start justify-between gap-2">
                            <label class="block text-xs font-bold text-slate-700">{{ $label }}</label>
                            <span class="shrink-0 rounded-md border px-2 py-0.5 text-[9px] font-black uppercase {{ $typeStyles[$type] }}">{{ $type }}</span>
                        </div>
                        <p class="mb-2 min-h-8 text-[11px] leading-4 text-slate-500">{{ $description }}</p>
                        <input type="hidden" name="original_bodym_{{ $name }}" value="{{ $value }}">
                        <div class="flex items-center gap-2">
                            <input type="number" step="0.01" name="bodym_{{ $name }}" value="{{ $value }}" @input="edited = true"
                                class="flex-1 bg-white rounded-lg border-gray-200 text-base font-bold focus:border-blue-500 focus:ring-blue-500">
                            <span class="text-sm text-gray-400 font-medium">cm</span>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                            @if($fieldConfidence !== null)
                            <p class="text-[11px] text-blue-600">Confidence {{ round($fieldConfidence * 100) }}%</p>
                            @endif
                            @if($interval)
                            <p class="text-[11px] text-slate-500">Interval {{ $interval[0] }}-{{ $interval[1] }} cm</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>
            @endif

            <div class="flex items-center justify-between pt-5 mt-6 border-t border-gray-50">
                <p class="text-xs text-amber-600 font-medium" x-show="edited" x-cloak>Nilai telah diedit manual.</p>
                <div class="flex gap-3 ml-auto">
                    <a href="{{ route('user.measurement.index') }}" class="px-4 py-2.5 text-sm text-gray-500 hover:text-gray-700 font-medium">Batal</a>
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl font-semibold text-sm hover:bg-blue-700 transition-colors shadow-sm">
                        Simpan Ukuran Ini
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
