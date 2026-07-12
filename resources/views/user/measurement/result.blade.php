@extends('layouts.user')
@section('page-title', 'Hasil Analisis Ukuran')
@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6">
        <a href="{{ route('user.measurement.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Ukur Badan
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Hasil Analisis AI</h1>
        <p class="text-gray-500 text-sm mt-1">Periksa dan edit nilai ukuran sebelum menyimpan.</p>
    </div>

    {{-- Measurement summary + confidence --}}
    @php
        $isGood = $confidence >= 0.7;
        $chestVal = floatval($data['chest'] ?? 0);
        $waistVal = floatval($data['waist'] ?? 0);
        $hipsVal = floatval($data['hips'] ?? 0);
    @endphp

    {{-- Ringkasan ukuran pokok --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mb-6">
        <p class="text-sm font-semibold text-gray-700 mb-3">Ringkasan Ukuran Pokok</p>
        <div class="grid grid-cols-3 gap-3">
            <div class="bg-blue-50 rounded-xl border border-blue-100 p-3 text-center">
                <p class="text-[11px] text-blue-500 font-semibold mb-1">Dada</p>
                <p class="text-xl font-black text-blue-700">{{ number_format($chestVal, 1) }}<span class="text-xs font-semibold"> cm</span></p>
            </div>
            <div class="bg-emerald-50 rounded-xl border border-emerald-100 p-3 text-center">
                <p class="text-[11px] text-emerald-500 font-semibold mb-1">Pinggang</p>
                <p class="text-xl font-black text-emerald-700">{{ number_format($waistVal, 1) }}<span class="text-xs font-semibold"> cm</span></p>
            </div>
            <div class="bg-violet-50 rounded-xl border border-violet-100 p-3 text-center">
                <p class="text-[11px] text-violet-500 font-semibold mb-1">Pinggul</p>
                <p class="text-xl font-black text-violet-700">{{ number_format($hipsVal, 1) }}<span class="text-xs font-semibold"> cm</span></p>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-3">Sistem menyimpan hasil estimasi ukuran badan dalam sentimeter untuk kebutuhan pola pokok jahit custom, bukan ukuran standar S/M/L/XL.</p>
    </div>

    {{-- Confidence badge --}}
    @php $isGood = $confidence >= 0.7; @endphp
    <div class="rounded-2xl border p-4 mb-6 flex items-start gap-3 {{ $isGood ? 'bg-green-50 border-green-200' : 'bg-amber-50 border-amber-200' }}">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 {{ $isGood ? 'bg-green-100' : 'bg-amber-100' }}">
            @if($isGood)
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @else
                <svg class="w-5 h-5 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            @endif
        </div>
        <div>
            <p class="text-sm font-bold {{ $isGood ? 'text-green-800' : 'text-amber-800' }}">
                Tingkat Keyakinan: {{ round($confidence * 100) }}%
            </p>
            <p class="text-xs mt-0.5 {{ $isGood ? 'text-green-600' : 'text-amber-600' }}">
                @if(!$refDetected)
                    Benda referensi tidak terdeteksi. Ukuran menggunakan estimasi — harap periksa dan edit manual jika perlu.
                @elseif($isGood)
                    Pose tubuh terdeteksi dengan baik. Nilai ukuran cukup akurat.
                @else
                    Beberapa titik tubuh kurang terlihat. Periksa dan edit manual jika perlu.
                @endif
            </p>
        </div>
    </div>

    {{-- Form Edit & Simpan --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden" x-data="{ edited: false }">
        <div class="px-6 py-5 border-b border-gray-50">
            <h2 class="font-bold text-gray-900">Estimasi Ukuran Badan</h2>
            <p class="text-sm text-gray-500 mt-0.5">Anda dapat mengubah nilai di bawah ini sebelum menyimpan.</p>
        </div>

        <form action="{{ route('user.measurement.store') }}" method="POST" class="p-6">
            @csrf
            <input type="hidden" name="photo_path" value="{{ $photoPath }}">
            <input type="hidden" name="ref_object" value="{{ $refObject }}">
            <input type="hidden" name="ref_size" value="{{ $refSize }}">
            <input type="hidden" name="is_edited" x-bind:value="edited ? 1 : 0">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                @php
                    $fields = [
                        ['label' => 'Lingkar Dada', 'name' => 'chest', 'value' => $data['chest']],
                        ['label' => 'Lingkar Pinggang', 'name' => 'waist', 'value' => $data['waist']],
                        ['label' => 'Lingkar Pinggul', 'name' => 'hips', 'value' => $data['hips']],
                        ['label' => 'Lebar Bahu', 'name' => 'shoulder_width', 'value' => $data['shoulder_width']],
                        ['label' => 'Panjang Lengan', 'name' => 'arm_length', 'value' => $data['arm_length'] ?? 0],
                        ['label' => 'Estimasi Tinggi', 'name' => 'height', 'value' => $data['height_estimate']],
                    ];
                @endphp
                @foreach($fields as $field)
                <div class="bg-gray-50 rounded-xl p-4">
                    <label class="block text-xs font-semibold text-gray-500 mb-2">{{ $field['label'] }} (cm)</label>
                    <div class="flex items-center gap-2">
                        <input type="number" step="0.01" name="{{ $field['name'] }}" value="{{ $field['value'] }}"
                            @input="edited = true"
                            class="flex-1 bg-white rounded-lg border-gray-200 text-lg font-bold focus:border-blue-500 focus:ring-blue-500">
                        <span class="text-sm text-gray-400 font-medium">cm</span>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-50">
                <p class="text-xs text-amber-600 font-medium flex items-center gap-1" x-show="edited" x-cloak>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Nilai telah diedit
                </p>
                <div class="flex gap-3 ml-auto">
                    <a href="{{ route('user.measurement.index') }}" class="px-4 py-2.5 text-sm text-gray-500 hover:text-gray-700 font-medium">Batal</a>
                    <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 text-white rounded-xl font-semibold text-sm hover:bg-blue-700 transition-colors shadow-sm">
                        Simpan Ukuran Ini
                    </button>
                </div>
            </div>
        </form>
    </div>

</div>
@endsection
