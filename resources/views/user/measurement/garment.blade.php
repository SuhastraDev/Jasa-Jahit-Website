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

    @if(!empty(session('markerIssues')))
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
        <p class="flex items-center gap-2 font-bold">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 shrink-0"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            KTP/A4 tidak terbaca untuk: {{ implode(', ', session('markerIssues')) }}
        </p>
        <ul class="mt-2 list-disc space-y-1 pl-9">
            <li>Letakkan KTP atau kertas A4 RATA di permukaan yang sama dengan pakaian (bukan miring/berdiri/menempel benda lain).</li>
            <li>Taruh di SAMPING pakaian dengan jarak wajar, seluruh bagian marker harus terlihat penuh (tidak terpotong tepi foto atau tertutup kain).</li>
            <li>Pastikan pencahayaan cukup dan tidak ada bayangan tebal menutupi marker.</li>
            <li>Foto tegak lurus dari atas, bukan dari sudut miring.</li>
        </ul>
    </div>
    @endif

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
                            <div class="mb-4 flex flex-col items-center gap-3 rounded-lg bg-slate-50 p-3 sm:flex-row sm:items-start">
                                <svg viewBox="0 0 300 280" class="h-40 w-40 shrink-0">
                                    <path d="M110,18 L190,18 L232,58 L272,95 L242,120 L212,92 L212,258 L88,258 L88,92 L58,120 L28,95 L68,58 Z"
                                        fill="#dbeafe" stroke="#3b82f6" stroke-width="3"/>
                                    <line x1="70" y1="55" x2="230" y2="55" stroke="#f97316" stroke-width="2.5" stroke-dasharray="5 3"/>
                                    <line x1="90" y1="92" x2="210" y2="92" stroke="#f97316" stroke-width="2.5" stroke-dasharray="5 3"/>
                                    <line x1="95" y1="150" x2="205" y2="150" stroke="#f97316" stroke-width="2.5" stroke-dasharray="5 3"/>
                                    <line x1="90" y1="195" x2="210" y2="195" stroke="#f97316" stroke-width="2.5" stroke-dasharray="5 3"/>
                                    <line x1="70" y1="55" x2="43" y2="107" stroke="#f97316" stroke-width="2.5" stroke-dasharray="5 3"/>
                                    <line x1="150" y1="20" x2="150" y2="256" stroke="#f97316" stroke-width="2.5" stroke-dasharray="5 3"/>
                                    @foreach([['1',150,50],['2',150,87],['3',150,145],['4',150,190],['5',56,80],['6',239,73],['7',43,107],['8',249,107],['9',150,270]] as [$num,$x,$y])
                                        <circle cx="{{ $x }}" cy="{{ $y }}" r="10" fill="#2563eb"/>
                                        <text x="{{ $x }}" y="{{ $y + 4 }}" text-anchor="middle" font-size="11" font-weight="800" fill="white">{{ $num }}</text>
                                    @endforeach
                                </svg>
                                <div class="flex-1 text-xs text-gray-600">
                                    <p class="mb-1.5 font-bold text-gray-800">Foto dari atas, bagian DEPAN baju menghadap ke atas (kerah &amp; kancing terlihat).</p>
                                    <ol class="grid grid-cols-2 gap-x-3 gap-y-0.5 list-none">
                                        <li><b>1</b> Lebar Bahu</li>
                                        <li><b>2</b> Lingkar Dada</li>
                                        <li><b>3</b> Pinggang</li>
                                        <li><b>4</b> Pinggul</li>
                                        <li><b>5</b> Panjang Lengan</li>
                                        <li><b>6</b> Lingkar Lengan</li>
                                        <li><b>7</b> Lobang Tangan</li>
                                        <li><b>8</b> Lingkar Tangan</li>
                                        <li><b>9</b> Panjang Badan</li>
                                    </ol>
                                </div>
                            </div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Foto baju rata + KTP/A4 di sampingnya</label>
                            <input type="file" name="shirt_photo" accept="image/jpeg,image/png,image/webp" @change="previewPhoto($event, 'shirt')"
                                class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-700">
                            <img x-show="preview.shirt" :src="preview.shirt" x-cloak alt="Preview foto baju" class="mt-2 max-h-56 w-full rounded-lg border border-gray-200 object-contain">
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
                            <div class="mb-4 flex flex-col items-center gap-3 rounded-lg bg-slate-50 p-3 sm:flex-row sm:items-start">
                                <svg viewBox="0 0 260 320" class="h-40 w-40 shrink-0">
                                    <path d="M70,20 L190,20 L190,140 L225,300 L175,300 L150,150 L110,150 L85,300 L35,300 L70,140 Z"
                                        fill="#dbeafe" stroke="#3b82f6" stroke-width="3"/>
                                    <line x1="70" y1="20" x2="190" y2="20" stroke="#f97316" stroke-width="2.5" stroke-dasharray="5 3"/>
                                    <line x1="70" y1="140" x2="190" y2="140" stroke="#f97316" stroke-width="2.5" stroke-dasharray="5 3"/>
                                    <line x1="130" y1="20" x2="130" y2="150" stroke="#f97316" stroke-width="2.5" stroke-dasharray="5 3"/>
                                    <line x1="70" y1="20" x2="35" y2="300" stroke="#f97316" stroke-width="2.5" stroke-dasharray="5 3"/>
                                    <line x1="35" y1="300" x2="85" y2="300" stroke="#f97316" stroke-width="2.5" stroke-dasharray="5 3"/>
                                    <line x1="97" y1="172" x2="183" y2="172" stroke="#f97316" stroke-width="2.5" stroke-dasharray="5 3"/>
                                    @foreach([['1',130,15],['2',130,135],['3',115,85],['4',52,160],['5',60,300],['6',140,172]] as [$num,$x,$y])
                                        <circle cx="{{ $x }}" cy="{{ $y }}" r="10" fill="#2563eb"/>
                                        <text x="{{ $x }}" y="{{ $y + 4 }}" text-anchor="middle" font-size="11" font-weight="800" fill="white">{{ $num }}</text>
                                    @endforeach
                                </svg>
                                <div class="flex-1 text-xs text-gray-600">
                                    <p class="mb-1.5 font-bold text-gray-800">Foto dari atas, kedua kaki celana terentang rata &amp; simetris.</p>
                                    <ol class="grid grid-cols-2 gap-x-3 gap-y-0.5 list-none">
                                        <li><b>1</b> Lingkar Pinggang</li>
                                        <li><b>2</b> Lingkar Pinggul</li>
                                        <li><b>3</b> Panjang Pesak</li>
                                        <li><b>4</b> Panjang Celana</li>
                                        <li><b>5</b> Lingkar Kaki</li>
                                        <li><b>6</b> Lingkar Paha</li>
                                    </ol>
                                </div>
                            </div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Foto celana rata + KTP/A4 di sampingnya</label>
                            <input type="file" name="pants_photo" accept="image/jpeg,image/png,image/webp" @change="previewPhoto($event, 'pants')"
                                class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-700">
                            <img x-show="preview.pants" :src="preview.pants" x-cloak alt="Preview foto celana" class="mt-2 max-h-56 w-full rounded-lg border border-gray-200 object-contain">
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
                            <div class="mb-4 flex flex-col items-center gap-3 rounded-lg bg-slate-50 p-3 sm:flex-row sm:items-start">
                                <svg viewBox="0 0 260 260" class="h-40 w-40 shrink-0">
                                    <path d="M100,20 L160,20 L200,240 L60,240 Z" fill="#dbeafe" stroke="#3b82f6" stroke-width="3"/>
                                    <line x1="100" y1="20" x2="160" y2="20" stroke="#f97316" stroke-width="2.5" stroke-dasharray="5 3"/>
                                    <line x1="90" y1="80" x2="170" y2="80" stroke="#f97316" stroke-width="2.5" stroke-dasharray="5 3"/>
                                    <line x1="130" y1="20" x2="130" y2="240" stroke="#f97316" stroke-width="2.5" stroke-dasharray="5 3"/>
                                    <line x1="60" y1="240" x2="200" y2="240" stroke="#f97316" stroke-width="2.5" stroke-dasharray="5 3"/>
                                    @foreach([['1',130,15],['2',130,75],['3',150,130],['4',130,245]] as [$num,$x,$y])
                                        <circle cx="{{ $x }}" cy="{{ $y }}" r="10" fill="#2563eb"/>
                                        <text x="{{ $x }}" y="{{ $y + 4 }}" text-anchor="middle" font-size="11" font-weight="800" fill="white">{{ $num }}</text>
                                    @endforeach
                                </svg>
                                <div class="flex-1 text-xs text-gray-600">
                                    <p class="mb-1.5 font-bold text-gray-800">Foto dari atas, rok direbahkan simetris dari pinggang ke bawah.</p>
                                    <ol class="grid grid-cols-2 gap-x-3 gap-y-0.5 list-none">
                                        <li><b>1</b> Pinggang</li>
                                        <li><b>2</b> Pinggul</li>
                                        <li><b>3</b> Panjang Rok</li>
                                        <li><b>4</b> Keliling Bawah Rok</li>
                                    </ol>
                                    <p class="mt-1.5 text-[11px] text-gray-400">Rok Sekolah cuma butuh Pinggang (1) &amp; Panjang Rok (3).</p>
                                </div>
                            </div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Jenis rok</label>
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <label class="flex items-center gap-2 rounded-lg border border-gray-200 p-3 cursor-pointer has-[:checked]:border-blue-400 has-[:checked]:bg-blue-50">
                                    <input type="radio" name="skirt_subtype" value="sempit" checked class="text-blue-600">
                                    <span class="text-sm font-semibold text-gray-700">Rok Sempit</span>
                                </label>
                                <label class="flex items-center gap-2 rounded-lg border border-gray-200 p-3 cursor-pointer has-[:checked]:border-blue-400 has-[:checked]:bg-blue-50">
                                    <input type="radio" name="skirt_subtype" value="sekolah" class="text-blue-600">
                                    <span class="text-sm font-semibold text-gray-700">Rok Sekolah</span>
                                </label>
                            </div>
                            <label class="block text-xs font-semibold text-gray-600 mb-2">Foto rok rata + KTP/A4 di sampingnya</label>
                            <input type="file" name="skirt_photo" accept="image/jpeg,image/png,image/webp" @change="previewPhoto($event, 'skirt')"
                                class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-700">
                            <img x-show="preview.skirt" :src="preview.skirt" x-cloak alt="Preview foto rok" class="mt-2 max-h-56 w-full rounded-lg border border-gray-200 object-contain">
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
                        <div class="relative border border-gray-100 rounded-xl p-4 transition-colors hover:border-blue-200 hover:bg-blue-50/30">
                            <form action="{{ route('user.measurement.destroy', $m) }}" method="POST" onsubmit="return confirm('Hapus data ukuran ini?')" class="absolute right-3 top-3 z-10">
                                @csrf @method('DELETE')
                                <button type="submit" title="Hapus data ukuran"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                        <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6h16Z"/>
                                        <path d="M10 11v6M14 11v6"/>
                                    </svg>
                                </button>
                            </form>
                            <a href="{{ route('user.measurement.show', $m) }}" class="block pr-10">
                                <div class="mb-3">
                                    <p class="text-xs font-semibold text-gray-500">{{ $m->created_at->format('d M Y, H:i') }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $m->measurement_method_label }} - {{ $m->ref_object_label }}</p>
                                    @if($m->confidence_score)
                                    <p class="text-xs text-blue-600 mt-1">Confidence {{ round((float) $m->confidence_score * 100) }}%</p>
                                    @endif
                                    @if($m->is_edited)<span class="inline-block mt-1 text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full font-medium">Diedit</span>@endif
                                </div>
                                <dl class="grid grid-cols-3 gap-1.5">
                                    @foreach([['Lingkar Dada',$m->chest],['Lebar Bahu',$m->shoulder_width],['Panjang Baju',$m->shirt_length],['Pinggang Celana',$m->pants_waist],['Pinggul Celana',$m->pants_hips],['Inseam',$m->inseam]] as [$lbl,$val])
                                    <div class="bg-gray-50 rounded-lg p-2 text-center">
                                        <dt class="text-[10px] text-gray-400 font-medium mb-0.5">{{ $lbl }}</dt>
                                        <dd class="text-xs font-bold text-gray-800">{{ $val ?? '-' }}<span class="font-normal text-gray-400">cm</span></dd>
                                    </div>
                                    @endforeach
                                </dl>
                            </a>
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
            preview: { shirt: null, pants: null, skirt: null },
            previewPhoto(event, key) {
                const file = event.target.files[0];
                if (this.preview[key]) URL.revokeObjectURL(this.preview[key]);
                this.preview[key] = file ? URL.createObjectURL(file) : null;
            },
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
