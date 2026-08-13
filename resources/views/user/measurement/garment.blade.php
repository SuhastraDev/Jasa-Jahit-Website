@extends('layouts.user')
@section('page-title', 'Ukur Baju/Celana')
@section('content')

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8" x-data="garmentUpload()">

    <div x-show="submitting" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 backdrop-blur-sm">
        <div class="mx-4 w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl">
            <svg class="mx-auto h-12 w-12 animate-spin text-blue-600" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="mt-4 text-sm font-black text-slate-900" x-text="stages[stage]"></p>
            <div class="mt-4 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-blue-600 transition-all duration-500"
                    :style="`width: ${((stage + 1) / stages.length) * 100}%`"></div>
            </div>
        </div>
    </div>

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Ukur dari Baju/Celana</h1>
        <p class="text-gray-500 text-sm mt-1">
            Letakkan baju atau celana yang sudah pas secara RATA di lantai/meja dengan alas polos,
            taruh KTP atau kertas A4 di sampingnya (di permukaan yang sama), lalu foto dari atas.
        </p>
    </div>

    @if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4 text-green-700 text-sm">{{ session('success') }}</div>
    @endif

    @if(!empty(session('debugImages')))
    <div class="mb-6">
        <p class="mb-3 text-xs font-black uppercase tracking-wide text-slate-500">Yang Dibaca Sistem dari Foto Terakhir</p>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach(session('debugImages') as $label => $path)
                <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600">{{ $label }}</div>
                    <img src="{{ asset('storage/' . $path) }}" alt="Deteksi {{ $label }}" class="w-full">
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <form action="{{ route('user.measurement.garment-analyze') }}" method="POST" enctype="multipart/form-data" class="space-y-6"
                    @submit="startSubmitAnimation">
                    @csrf

                    <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-4">
                        <p class="text-sm font-bold text-blue-900 mb-2">Cara foto yang benar</p>
                        <ul class="text-xs text-blue-800 space-y-1 list-disc pl-4">
                            <li>Ratakan pakaian — tidak terlipat, tidak kusut, lengan/kaki terentang wajar.</li>
                            <li>Gunakan alas polos yang warnanya kontras dengan pakaian.</li>
                            <li>KTP/A4 diletakkan RATA di sebelah pakaian, di permukaan/lantai yang sama.</li>
                            <li>Foto tegak lurus dari atas (bukan miring), pencahayaan cukup.</li>
                        </ul>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-800 mb-2">Benda referensi</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 p-3 cursor-pointer has-[:checked]:border-blue-400 has-[:checked]:bg-blue-50">
                                <input type="radio" name="ref_object" value="a4" checked class="text-blue-600">
                                <span class="text-sm font-semibold text-gray-700">Kertas A4 (21 x 29,7 cm)</span>
                            </label>
                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 p-3 cursor-pointer has-[:checked]:border-blue-400 has-[:checked]:bg-blue-50">
                                <input type="radio" name="ref_object" value="ktp" class="text-blue-600">
                                <span class="text-sm font-semibold text-gray-700">KTP (8,56 x 5,398 cm)</span>
                            </label>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-5">
                        <label class="flex items-center gap-2 mb-3 cursor-pointer">
                            <input type="checkbox" x-model="wantShirt" class="rounded text-blue-600">
                            <span class="text-sm font-bold text-gray-800">Ukur Baju</span>
                        </label>
                        <div x-show="wantShirt" x-cloak class="rounded-lg border border-gray-200 p-4">
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Foto baju rata + KTP/A4 di sampingnya</label>
                            <input type="file" name="shirt_photo" accept="image/jpeg,image/png,image/webp"
                                class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-700">
                            <input type="hidden" name="shirt_reference_box" value="">
                            @error('shirt_photo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-5">
                        <label class="flex items-center gap-2 mb-3 cursor-pointer">
                            <input type="checkbox" x-model="wantPants" class="rounded text-blue-600">
                            <span class="text-sm font-bold text-gray-800">Ukur Celana</span>
                        </label>
                        <div x-show="wantPants" x-cloak class="rounded-lg border border-gray-200 p-4">
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Foto celana rata + KTP/A4 di sampingnya</label>
                            <input type="file" name="pants_photo" accept="image/jpeg,image/png,image/webp"
                                class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-700">
                            <input type="hidden" name="pants_reference_box" value="">
                            @error('pants_photo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-5">
                        <label class="flex items-center gap-2 mb-3 cursor-pointer">
                            <input type="checkbox" x-model="wantSkirt" class="rounded text-blue-600">
                            <span class="text-sm font-bold text-gray-800">Ukur Rok</span>
                        </label>
                        <div x-show="wantSkirt" x-cloak class="rounded-lg border border-gray-200 p-4">
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Foto rok rata + KTP/A4 di sampingnya</label>
                            <input type="file" name="skirt_photo" accept="image/jpeg,image/png,image/webp"
                                class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-700">
                            <input type="hidden" name="skirt_reference_box" value="">
                            @error('skirt_photo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-blue-600 px-6 py-3 text-sm font-black text-white shadow-sm transition-colors hover:bg-blue-700">
                        Validasi dan Hitung Ukuran
                    </button>
                </form>
            </div>
        </div>

        <div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h2 class="font-bold text-gray-900 mb-5">Riwayat Pengukuran</h2>

                @if($measurements->isEmpty())
                    <div class="py-10 text-center">
                        <p class="text-gray-500 font-medium text-sm">Belum ada data ukuran</p>
                        <p class="text-xs text-gray-400 mt-0.5">Upload foto baju/celana untuk analisis.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($measurements as $m)
                        <div class="border border-gray-100 rounded-xl p-4">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <p class="text-xs font-semibold text-gray-500">{{ $m->created_at->format('d M Y, H:i') }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $m->measurement_method_label }} - {{ $m->ref_object_label }}</p>
                                    @if($m->confidence_score)
                                    <p class="text-xs text-blue-600 mt-1">Confidence {{ round((float) $m->confidence_score * 100) }}%</p>
                                    @endif
                                    @if($m->is_edited)<span class="inline-block mt-1 text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full font-medium">Diedit</span>@endif
                                </div>
                                <form action="{{ route('user.measurement.destroy', $m) }}" method="POST" onsubmit="return confirm('Hapus data ukuran ini?')">
                                    @csrf @method('DELETE')
                                    <button class="text-gray-300 hover:text-red-500 transition-colors p-0.5">Hapus</button>
                                </form>
                            </div>
                            <dl class="grid grid-cols-3 gap-1.5">
                                @foreach([['Lingkar Dada',$m->chest],['Lebar Bahu',$m->shoulder_width],['Panjang Baju',$m->shirt_length],['Pinggang Celana',$m->pants_waist],['Pinggul Celana',$m->pants_hips],['Inseam',$m->inseam]] as [$lbl,$val])
                                <div class="bg-gray-50 rounded-lg p-2 text-center">
                                    <dt class="text-[10px] text-gray-400 font-medium mb-0.5">{{ $lbl }}</dt>
                                    <dd class="text-xs font-bold text-gray-800">{{ $val ?? '-' }}<span class="font-normal text-gray-400">cm</span></dd>
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

@push('scripts')
<script>
    function garmentUpload() {
        return {
            wantShirt: true,
            wantPants: false,
            wantSkirt: false,
            submitting: false,
            stage: 0,
            // Mirrors the actual backend pipeline order (measurement.py's
            // calculate_scale -> segment_garment -> detect_*_keypoints ->
            // measure_*), advanced on a timer since the request itself is
            // one synchronous call with no server-sent progress events.
            stages: [
                'Mengunggah foto...',
                'Mendeteksi benda patokan (KTP/A4)...',
                'Memisahkan siluet pakaian dari latar...',
                'Mencari titik ukur (bahu, ketiak, kerah, dsb)...',
                'Menghitung ukuran dalam cm...',
            ],
            startSubmitAnimation() {
                this.submitting = true;
                this.stage = 0;
                const timer = setInterval(() => {
                    if (this.stage < this.stages.length - 1) {
                        this.stage++;
                    } else {
                        clearInterval(timer);
                    }
                }, 900);
            },
        };
    }
</script>
@endpush
@endsection
