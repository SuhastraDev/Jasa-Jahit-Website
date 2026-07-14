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
            ['Leher', 'neck'],
            ['Dada', 'chest'],
            ['Pinggang', 'waist'],
            ['Pinggul', 'hips'],
            ['Lebar Bahu', 'shoulder_width'],
            ['Panjang Baju', 'shirt_length'],
            ['Panjang Lengan', 'arm_length'],
            ['Lengan Atas', 'upper_arm'],
            ['Pergelangan', 'wrist'],
            ['Tinggi', 'height'],
        ];
        $pantsFields = [
            ['Pinggang Celana', 'pants_waist'],
            ['Pinggul Celana', 'pants_hips'],
            ['Paha', 'thigh'],
            ['Lutut', 'knee'],
            ['Betis', 'calf'],
            ['Bukaan Bawah', 'ankle'],
            ['Inseam', 'inseam'],
            ['Outseam', 'outseam'],
            ['Rise/Pesak', 'rise'],
        ];
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="rounded-xl border p-4 {{ $isGood ? 'bg-green-50 border-green-200' : 'bg-amber-50 border-amber-200' }}">
            <p class="text-xs font-semibold {{ $isGood ? 'text-green-700' : 'text-amber-700' }}">Confidence</p>
            <p class="text-2xl font-black {{ $isGood ? 'text-green-800' : 'text-amber-800' }}">{{ $confidencePct }}%</p>
        </div>
        <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
            <p class="text-xs font-semibold text-blue-700">Quality Score</p>
            <p class="text-2xl font-black text-blue-800">{{ $qualityPct }}%</p>
        </div>
        <div class="rounded-xl border {{ $refDetected ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }} p-4">
            <p class="text-xs font-semibold {{ $refDetected ? 'text-green-700' : 'text-red-700' }}">Marker</p>
            <p class="text-sm font-bold {{ $refDetected ? 'text-green-800' : 'text-red-800' }}">{{ $refDetected ? 'Terdeteksi pada semua foto' : 'Tidak lengkap' }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden" x-data="{ edited: false }">
        <div class="px-6 py-5 border-b border-gray-50">
            <h2 class="font-bold text-gray-900">Estimasi Ukuran Badan</h2>
            <p class="text-sm text-gray-500 mt-0.5">Field dapat diedit. Sistem akan menyimpan field yang berubah sebagai koreksi manual.</p>
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
            <input type="hidden" name="confidence_score" value="{{ $confidence }}">
            <input type="hidden" name="quality_score" value="{{ $qualityScore }}">
            <input type="hidden" name="raw_cv_json" value='@json($rawCvJson)'>
            <input type="hidden" name="is_edited" x-bind:value="edited ? 1 : 0">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <section>
                    <h3 class="text-sm font-bold text-gray-900 mb-3">Ukuran Baju</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($topFields as [$label, $name])
                        @php
                            $value = $data[$name] ?? 0;
                            $fieldConfidence = $perFieldConfidence[$name] ?? null;
                        @endphp
                        <div class="bg-gray-50 rounded-xl p-4">
                            <label class="block text-xs font-semibold text-gray-500 mb-2">{{ $label }} (cm)</label>
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
                        @foreach($pantsFields as [$label, $name])
                        @php
                            $value = $data[$name] ?? 0;
                            $fieldConfidence = $perFieldConfidence[$name] ?? null;
                        @endphp
                        <div class="bg-gray-50 rounded-xl p-4">
                            <label class="block text-xs font-semibold text-gray-500 mb-2">{{ $label }} (cm)</label>
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
