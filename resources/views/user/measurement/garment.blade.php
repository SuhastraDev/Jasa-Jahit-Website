@extends('layouts.user')
@section('page-title', 'Ukur Baju/Celana')
@section('content')

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8" x-data="{ wantShirt: true, wantPants: false }">

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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <form action="{{ route('user.measurement.garment-analyze') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
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
@endsection
