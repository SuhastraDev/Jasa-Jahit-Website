@extends('layouts.user')
@section('page-title', 'Hasil Pengukuran')
@section('content')
<div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-8">
    @php
        $measurementSections = [
            [
                'title' => 'Ukuran Baju',
                'caption' => 'Ukuran utama untuk membentuk bagian badan dan lengan baju.',
                'eyebrow' => 'BAGIAN ATAS',
                'dot' => 'bg-blue-600',
                'fields' => [
                    ['Lebar bahu', 'shoulder_width', 'lebar', 'Jarak lurus dari ujung bahu kiri ke ujung bahu kanan.'],
                    ['Lingkar dada', 'chest', 'lingkar', 'Keliling bagian dada yang paling lebar.'],
                    ['Lingkar pinggang', 'waist', 'lingkar', 'Keliling pinggang badan pada posisi alami.'],
                    ['Lingkar pinggul', 'hips', 'lingkar', 'Keliling bagian pinggul yang paling lebar.'],
                    ['Panjang lengan', 'arm_length', 'panjang', 'Jarak dari ujung bahu hingga pergelangan tangan.'],
                    ['Lingkar tangan', 'wrist', 'lingkar', 'Keliling pergelangan tangan untuk menentukan bukaan lengan.'],
                    ['Panjang badan', 'shirt_length', 'panjang', 'Jarak dari bahu hingga batas bawah badan baju.'],
                ],
            ],
            [
                'title' => 'Ukuran Celana',
                'caption' => 'Ukuran utama untuk membentuk pinggang, pesak, dan bagian kaki celana.',
                'eyebrow' => 'BAGIAN BAWAH',
                'dot' => 'bg-emerald-600',
                'fields' => [
                    ['Lingkar pinggang', 'pants_waist', 'lingkar', 'Keliling pada posisi ban pinggang celana.'],
                    ['Lingkar pinggul', 'pants_hips', 'lingkar', 'Keliling bagian pinggul yang paling lebar.'],
                    ['Panjang pesak', 'rise', 'panjang', 'Jarak dari garis pinggang hingga titik selangkangan.'],
                    ['Panjang celana', 'outseam', 'panjang', 'Jarak dari pinggang hingga pergelangan kaki bagian luar.'],
                    ['Lingkar kaki bagian bawah', 'ankle', 'lingkar', 'Keliling pergelangan kaki untuk menentukan bukaan bawah celana.'],
                    ['Lingkar kaki bagian atas', 'thigh', 'lingkar', 'Keliling bagian paha yang paling lebar.'],
                ],
            ],
        ];
        $typeStyles = [
            'lingkar' => 'border-violet-200 bg-violet-50 text-violet-700',
            'lebar' => 'border-cyan-200 bg-cyan-50 text-cyan-700',
            'panjang' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        ];
        $allMeasurementFields = [
            'neck', 'chest', 'waist', 'hips', 'shoulder_width', 'shirt_length',
            'arm_length', 'upper_arm', 'wrist', 'height', 'pants_waist', 'pants_hips',
            'thigh', 'knee', 'calf', 'ankle', 'inseam', 'outseam', 'rise',
        ];
        $visibleMeasurementFields = collect($measurementSections)
            ->flatMap(fn ($section) => collect($section['fields'])->pluck(1))
            ->all();
        $bodymPerFieldConfidence = $bodymMetadata['per_field_confidence'] ?? [];
        $bodymPredictionIntervals = $bodymMetadata['prediction_intervals_cm'] ?? [];
        $isReady = ($confidence ?? 0) >= 0.7 && ($qualityScore ?? 0) >= 0.7;
    @endphp

    <header class="mb-6 border-b border-slate-200 pb-5">
        <a href="{{ route('user.measurement.garment-index') }}" class="inline-flex min-h-10 items-center text-sm font-semibold text-slate-500 transition-colors hover:text-slate-800">
            Kembali ke Ukur Baju/Celana
        </a>
        <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-black text-blue-600">HASIL PENGUKURAN</p>
                <h1 class="mt-1 text-2xl font-black text-slate-950 sm:text-3xl">Hasil Pengukuran Baju dan Celana</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Periksa 13 ukuran utama berikut. Setiap nilai dapat dikoreksi sebelum disimpan dan digunakan untuk pesanan jahit.</p>
            </div>
            <div class="inline-flex w-fit items-center gap-2 rounded-lg border px-3 py-2 text-xs font-bold {{ $isReady ? 'border-green-200 bg-green-50 text-green-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
                <span class="h-2 w-2 rounded-full {{ $isReady ? 'bg-green-500' : 'bg-amber-500' }}"></span>
                {{ $isReady ? 'Hasil siap diperiksa' : 'Periksa kembali nilainya' }}
            </div>
        </div>
    </header>

    @if(!empty($partialErrors))
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <p class="font-bold">Sebagian foto tidak berhasil dianalisis:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach($partialErrors as $partialError)
                    <li>{{ $partialError }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('user.measurement.store') }}" method="POST" x-data="{ edited: false }">
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
        <input type="hidden" name="measurement_method" value="{{ $measurementMethod ?? '' }}">
        <input type="hidden" name="is_edited" x-bind:value="edited ? 1 : 0">

        @foreach($allMeasurementFields as $field)
            @continue(in_array($field, $visibleMeasurementFields, true))
            <input type="hidden" name="{{ $field }}" value="{{ $data[$field] ?? '' }}">
            <input type="hidden" name="original_{{ $field }}" value="{{ $data[$field] ?? '' }}">
        @endforeach

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-10">
            @foreach($measurementSections as $section)
                <section aria-labelledby="{{ str($section['title'])->slug() }}">
                    <div class="mb-4 flex items-start gap-3 border-b border-slate-200 pb-4">
                        <span class="mt-1 h-10 w-1 shrink-0 rounded-full {{ $section['dot'] }}"></span>
                        <div class="min-w-0">
                            <p class="text-[10px] font-black {{ str_contains($section['dot'], 'blue') ? 'text-blue-600' : 'text-emerald-600' }}">{{ $section['eyebrow'] }}</p>
                            <h2 id="{{ str($section['title'])->slug() }}" class="mt-0.5 text-lg font-black text-slate-950">{{ $section['title'] }}</h2>
                            <p class="mt-1 text-xs leading-5 text-slate-500">{{ $section['caption'] }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach($section['fields'] as [$label, $name, $type, $description])
                            @php
                                $value = isset($data[$name]) && is_numeric($data[$name])
                                    ? round((float) $data[$name], 2)
                                    : '';
                            @endphp
                            <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition-shadow focus-within:border-blue-300 focus-within:shadow-md">
                                <div class="flex min-h-6 items-start justify-between gap-2">
                                    <label for="measurement-{{ $name }}" class="text-xs font-black leading-5 text-slate-800">{{ $label }}</label>
                                    <span class="shrink-0 rounded-md border px-2 py-0.5 text-[9px] font-black uppercase {{ $typeStyles[$type] }}">{{ $type }}</span>
                                </div>
                                <p class="mt-1 min-h-10 text-[11px] leading-5 text-slate-500">{{ $description }}</p>
                                <input type="hidden" name="original_{{ $name }}" value="{{ $value }}">
                                <div class="mt-3 flex h-11 items-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100">
                                    <input id="measurement-{{ $name }}" type="number" step="0.01" min="0" max="400" name="{{ $name }}" value="{{ $value }}" @input="edited = true"
                                        class="h-full min-w-0 flex-1 border-0 bg-transparent px-3 text-base font-black text-slate-950 focus:ring-0">
                                    <span class="flex h-full items-center border-l border-slate-200 bg-white px-3 text-xs font-bold text-slate-400">cm</span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <div class="sticky bottom-0 z-10 -mx-4 mt-8 border-t border-slate-200 bg-white/95 px-4 py-4 shadow-[0_-8px_24px_rgba(15,23,42,0.08)] backdrop-blur sm:relative sm:mx-0 sm:flex sm:items-center sm:justify-between sm:bg-transparent sm:px-0 sm:shadow-none">
            <p class="mb-3 text-xs font-semibold text-amber-700 sm:mb-0" x-show="edited" x-cloak>Perubahan manual akan ikut disimpan.</p>
            <div class="flex flex-col-reverse gap-2 sm:ml-auto sm:flex-row sm:gap-3">
                <a href="{{ route('user.measurement.garment-index') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-200 px-5 text-sm font-bold text-slate-600 transition-colors hover:bg-slate-50">Batal</a>
                <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-blue-600 px-6 text-sm font-black text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 focus:ring-offset-2">
                    Simpan Hasil Pengukuran
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
