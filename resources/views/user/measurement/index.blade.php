@extends('layouts.user')
@section('page-title', 'Ukur Badan')
@section('content')

<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8" x-data="{ refObject: '{{ old('ref_object', 'a4') }}', preview: null }">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Ukur Badan (AI)</h1>
        <p class="text-gray-500 text-sm mt-1">Upload satu foto full body dengan benda referensi. Sistem akan memvalidasi foto lalu menghitung ukuran tubuh otomatis.</p>
    </div>

    @if(session('photo_issues') && count(session('photo_issues')) > 0)
    <div class="mb-6 bg-red-50 border border-red-200 rounded-2xl p-5">
        <div class="flex items-start gap-3 mb-3">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="flex-1">
                <p class="font-bold text-red-800 mb-2">AI mendeteksi masalah pada foto Anda:</p>
                <ul class="space-y-1.5">
                    @foreach(session('photo_issues') as $issue)
                    <li class="flex items-start gap-2 text-sm text-red-700">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        {{ $issue }}
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @if(session('photo_suggestion'))
        <div class="mt-3 pt-3 border-t border-red-200 flex items-start gap-2 bg-amber-50 rounded-xl p-3">
            <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            <p class="text-sm text-amber-800"><span class="font-semibold">Saran perbaikan:</span> {{ session('photo_suggestion') }}</p>
        </div>
        @endif
    </div>
    @elseif(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 flex items-center gap-3 text-red-700 text-sm">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('error') }}
    </div>
    @endif

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3 text-green-700 text-sm">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-5">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-9 h-9 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h2 class="font-bold text-gray-900">Panduan Foto</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <ul class="space-y-2.5">
                        @foreach(['Satu orang berdiri tegak menghadap kamera', 'Seluruh tubuh terlihat dari kepala sampai kaki', 'Benda referensi terlihat jelas di samping tubuh', 'Pencahayaan cukup dan foto tidak buram'] as $guide)
                        <li class="flex items-start gap-2.5">
                            <span class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="text-sm text-gray-600">{{ $guide }}</span>
                        </li>
                        @endforeach
                    </ul>
                    <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4">
                        <p class="text-sm font-semibold text-blue-800 mb-2">Benda referensi yang didukung</p>
                        <p class="text-xs text-blue-700 leading-relaxed">Gunakan kertas A4, kartu ATM/KTP, atau benda custom dengan ukuran lebar dan tinggi yang diketahui. Benda referensi dipakai untuk mengubah jarak piksel menjadi sentimeter.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 bg-purple-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <h2 class="font-bold text-gray-900">Analisis Ukuran dengan Computer Vision</h2>
                </div>

                <form action="{{ route('user.measurement.analyze') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                    @csrf

                    <div>
                        <label for="ref_object" class="block text-sm font-semibold text-gray-700 mb-1.5">Benda Referensi <span class="text-red-500">*</span></label>
                        <select name="ref_object" id="ref_object" x-model="refObject" class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="a4">Kertas A4 (21 x 29.7 cm)</option>
                            <option value="atm">Kartu ATM / KTP (8.56 x 5.4 cm)</option>
                            <option value="custom">Custom (ukuran sendiri)</option>
                        </select>
                    </div>

                    <div x-show="refObject === 'custom'" x-transition class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="ref_width_cm" class="block text-xs font-semibold text-gray-600 mb-1">Lebar benda (cm)</label>
                            <input type="number" step="0.1" name="ref_width_cm" id="ref_width_cm" value="{{ old('ref_width_cm') }}" class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Contoh: 21">
                        </div>
                        <div>
                            <label for="ref_height_cm" class="block text-xs font-semibold text-gray-600 mb-1">Tinggi benda (cm)</label>
                            <input type="number" step="0.1" name="ref_height_cm" id="ref_height_cm" value="{{ old('ref_height_cm') }}" class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Contoh: 29.7">
                        </div>
                    </div>

                    <div>
                        <label for="body_photo" class="block text-sm font-semibold text-gray-700 mb-1.5">Foto Full Body <span class="text-red-500">*</span></label>
                        <input type="file" name="body_photo" id="body_photo" accept="image/*" required
                               @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                        <p class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP. Maksimal 5MB.</p>
                        <div x-show="preview" class="mt-3">
                            <img :src="preview" class="max-h-64 rounded-xl border border-gray-200 shadow-sm object-contain" alt="Preview foto badan">
                        </div>
                    </div>

                    <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white rounded-xl font-bold text-sm hover:bg-blue-700 transition-colors shadow-sm flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        Analisis Ukuran Sekarang
                    </button>
                </form>
            </div>
        </div>

        <div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <h2 class="font-bold text-gray-900">Riwayat Pengukuran</h2>
                </div>

                @if($measurements->isEmpty())
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <p class="text-gray-500 font-medium text-sm">Belum ada data ukuran</p>
                        <p class="text-xs text-gray-400 mt-0.5">Upload foto untuk analisis AI.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($measurements as $m)
                        <div class="border border-gray-100 rounded-2xl p-4 hover:border-blue-100 transition-colors">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <p class="text-xs font-semibold text-gray-500">{{ $m->created_at->format('d M Y, H:i') }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $m->ref_object_label }}</p>
                                    @if($m->is_edited)<span class="inline-block mt-1 text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full font-medium">Diedit</span>@endif
                                </div>
                                <form action="{{ route('user.measurement.destroy', $m) }}" method="POST" onsubmit="return confirm('Hapus data ukuran ini?')">
                                    @csrf @method('DELETE')
                                    <button class="text-gray-300 hover:text-red-500 transition-colors p-0.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                            <dl class="grid grid-cols-3 gap-1.5">
                                @foreach([['Dada',$m->chest],['Pinggang',$m->waist],['Pinggul',$m->hips],['Bahu',$m->shoulder_width],['Lengan',$m->arm_length],['Tinggi',$m->height]] as [$lbl,$val])
                                <div class="bg-gray-50 rounded-lg p-2 text-center">
                                    <dt class="text-[10px] text-gray-400 font-medium mb-0.5">{{ $lbl }}</dt>
                                    <dd class="text-xs font-bold text-gray-800">{{ $val }}<span class="font-normal text-gray-400">cm</span></dd>
                                </div>
                                @endforeach
                            </dl>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
