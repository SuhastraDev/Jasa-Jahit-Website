@extends('layouts.user')
@section('page-title', 'Detail Ukuran')
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
                    ['Pinggang', 'shirt_waist', 'lingkar', 'Keliling pinggang badan pada posisi alami.'],
                    ['Pinggul', 'shirt_hips', 'lingkar', 'Keliling bagian pinggul yang paling lebar.'],
                    ['Panjang lengan', 'arm_length', 'panjang', 'Jarak dari ujung bahu hingga pergelangan tangan.'],
                    ['Lingkar lengan', 'upper_arm', 'lingkar', 'Keliling lengan atas (bisep), diukur sepertiga dari bahu ke arah manset.'],
                    ['Lobang tangan', 'sleeve_opening', 'lingkar', 'Keliling bukaan ujung lengan.'],
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
            [
                'title' => 'Ukuran Rok',
                'caption' => 'Ukuran utama untuk membentuk pinggang, pinggul, dan panjang rok.',
                'eyebrow' => 'ROK',
                'dot' => 'bg-purple-600',
                'fields' => [
                    ['Lingkar pinggang', 'waist', 'lingkar', 'Keliling pada posisi ban pinggang rok.'],
                    ['Lingkar pinggul', 'hips', 'lingkar', 'Keliling bagian pinggul yang paling lebar.'],
                    ['Panjang rok', 'skirt_length', 'panjang', 'Jarak dari pinggang hingga batas bawah rok.'],
                    ['Lebar bawah rok', 'hem_width', 'lingkar', 'Keliling pada bagian bawah/hem rok.'],
                ],
            ],
        ];
        $typeStyles = [
            'lingkar' => 'border-violet-200 bg-violet-50 text-violet-700',
            'lebar' => 'border-cyan-200 bg-cyan-50 text-cyan-700',
            'panjang' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        ];
        // Hide a section entirely if none of its fields have a saved value -
        // an older skirt-less save shouldn't render an empty "Ukuran Rok" block.
        $visibleSections = collect($measurementSections)->filter(
            fn ($section) => collect($section['fields'])->contains(fn ($f) => isset($data[$f[1]]))
        );
    @endphp

    <header class="mb-6 border-b border-slate-200 pb-5">
        <a href="{{ route('user.measurement.garment-index') }}" class="inline-flex min-h-10 items-center text-sm font-semibold text-slate-500 transition-colors hover:text-slate-800">
            Kembali ke Ukur Baju/Celana
        </a>
        <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-black text-blue-600">DATA TERSIMPAN</p>
                <h1 class="mt-1 text-2xl font-black text-slate-950 sm:text-3xl">Detail Ukuran</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Disimpan {{ $measurement->created_at->format('d M Y, H:i') }} &middot; {{ $measurement->measurement_method_label }} &middot; {{ $measurement->ref_object_label }}
                </p>
            </div>
            @if($measurement->is_edited)
                <div class="inline-flex w-fit items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700">
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                    Sebagian nilai pernah diedit manual
                </div>
            @endif
        </div>
    </header>

    @if(!empty($interactiveOverlays))
        <div class="mb-8">
            <p class="mb-1 text-xs font-black uppercase tracking-wide text-slate-500">Visual Deteksi — Titik &amp; Garis Ukur</p>
            <p class="mb-3 text-xs text-slate-500">Arahkan kursor ke titik biru atau garis oranye untuk lihat nilainya dalam cm.</p>
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach($interactiveOverlays as $label => $overlayData)
                    @php
                        $geometry = $overlayData['geometry'];
                        $pointsById = collect($geometry['points'])->keyBy('id');
                        $resolvePoint = function ($id) use (&$resolvePoint, $pointsById) {
                            $point = $pointsById[$id];
                            if (!empty($point['derived_from'])) {
                                [$a, $b] = array_map($resolvePoint, $point['derived_from']);
                                return ['x' => ($a['x'] + $b['x']) / 2, 'y' => ($a['y'] + $b['y']) / 2];
                            }
                            return ['x' => $point['x'], 'y' => $point['y']];
                        };
                    @endphp
                    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600">{{ $label }}</div>
                        <div class="relative" x-data="{ tip: null, tx: 0, ty: 0 }" x-ref="wrap{{ $loop->index }}"
                            @mousemove="const r = $el.getBoundingClientRect(); tx = $event.clientX - r.left; ty = $event.clientY - r.top;">
                            <img src="{{ $overlayData['photo_url'] }}" alt="Deteksi {{ $label }}" class="block w-full select-none" draggable="false">
                            <svg viewBox="0 0 {{ $geometry['image_width'] }} {{ $geometry['image_height'] }}" preserveAspectRatio="xMidYMid meet" class="absolute inset-0 h-full w-full">
                                @foreach($geometry['lines'] as $line)
                                    @php
                                        $pa = $resolvePoint($line['point_ids'][0]);
                                        $pb = $resolvePoint($line['point_ids'][1]);
                                    @endphp
                                    <line x1="{{ $pa['x'] }}" y1="{{ $pa['y'] }}" x2="{{ $pb['x'] }}" y2="{{ $pb['y'] }}"
                                        stroke="{{ !empty($line['custom']) ? '#7c3aed' : '#f97316' }}" stroke-width="5" stroke-linecap="round" opacity="0.75" class="cursor-pointer transition-opacity hover:opacity-100"
                                        @mouseenter="tip = '{{ addslashes($line['label']) }}: {{ $line['value_cm'] }} cm'"
                                        @mouseleave="tip = null"></line>
                                @endforeach
                                @foreach($geometry['points'] as $point)
                                    @php $resolved = $resolvePoint($point['id']); @endphp
                                    <circle cx="{{ $resolved['x'] }}" cy="{{ $resolved['y'] }}" r="11" fill="{{ str_starts_with($point['id'], 'custom_pt_') ? '#7c3aed' : '#2563eb' }}" stroke="white" stroke-width="3"
                                        class="cursor-pointer transition-opacity hover:opacity-80"
                                        @mouseenter="tip = '{{ addslashes($point['label']) }}'"
                                        @mouseleave="tip = null"></circle>
                                @endforeach
                            </svg>
                            <div x-show="tip" x-text="tip" x-cloak
                                class="pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-full rounded-md bg-slate-900 px-2.5 py-1.5 text-xs font-bold text-white shadow-lg"
                                :style="`left:${tx}px; top:${ty - 10}px`"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @elseif($measurement->front_photo_path || $measurement->side_photo_path || $measurement->back_photo_path)
        <div class="mb-8">
            <p class="mb-3 text-xs font-black uppercase tracking-wide text-slate-500">Foto</p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @foreach([['Depan', $measurement->front_photo_path], ['Samping', $measurement->side_photo_path], ['Belakang', $measurement->back_photo_path]] as [$label, $path])
                    @continue(!$path)
                    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600">{{ $label }}</div>
                        <img src="{{ asset('storage/' . $path) }}" alt="Foto {{ $label }}" class="w-full">
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-10">
        @forelse($visibleSections as $section)
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
                        @continue(!isset($data[$name]))
                        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex min-h-6 items-start justify-between gap-2">
                                <p class="text-xs font-black leading-5 text-slate-800">{{ $label }}</p>
                                <span class="shrink-0 rounded-md border px-2 py-0.5 text-[9px] font-black uppercase {{ $typeStyles[$type] }}">{{ $type }}</span>
                            </div>
                            <p class="mt-1 min-h-10 text-[11px] leading-5 text-slate-500">{{ $description }}</p>
                            <div class="mt-3 flex h-11 items-center rounded-lg border border-slate-200 bg-slate-50 px-3">
                                <span class="text-base font-black text-slate-950">{{ round((float) $data[$name], 2) }}</span>
                                <span class="ml-1 text-xs font-bold text-slate-400">cm</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <p class="text-sm text-slate-500">Tidak ada data ukuran tersimpan.</p>
        @endforelse
    </div>

    @if(!empty($customMeasurements))
        <div class="mt-8">
            <div class="mb-4 flex items-start gap-3 border-b border-slate-200 pb-4">
                <span class="mt-1 h-10 w-1 shrink-0 rounded-full bg-violet-600"></span>
                <div class="min-w-0">
                    <p class="text-[10px] font-black text-violet-600">TAMBAHAN</p>
                    <h2 class="mt-0.5 text-lg font-black text-slate-950">Ukuran Tambahan</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Ukuran custom yang ditambahkan manual di foto.</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach($customMeasurements as $custom)
                    <article class="rounded-lg border border-violet-200 bg-violet-50/40 p-4 shadow-sm">
                        <p class="text-xs font-black leading-5 text-slate-800">{{ $custom['label'] }}</p>
                        <p class="mt-1 text-[11px] leading-5 text-slate-500">{{ $custom['garment'] }}</p>
                        <div class="mt-3 flex h-11 items-center rounded-lg border border-slate-200 bg-white px-3">
                            <span class="text-base font-black text-slate-950">{{ round((float) $custom['value_cm'], 2) }}</span>
                            <span class="ml-1 text-xs font-bold text-slate-400">cm</span>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @endif

    <div class="sticky bottom-0 z-10 -mx-4 mt-8 border-t border-slate-200 bg-white/95 px-4 py-4 shadow-[0_-8px_24px_rgba(15,23,42,0.08)] backdrop-blur sm:relative sm:mx-0 sm:flex sm:items-center sm:justify-between sm:bg-transparent sm:px-0 sm:shadow-none">
        <div class="flex flex-col-reverse gap-2 sm:ml-auto sm:flex-row sm:gap-3">
            <a href="{{ route('user.measurement.garment-index') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-200 px-5 text-sm font-bold text-slate-600 transition-colors hover:bg-slate-50">Kembali</a>
            <form action="{{ route('user.measurement.destroy', $measurement) }}" method="POST" onsubmit="return confirm('Hapus data ukuran ini?')">
                @csrf @method('DELETE')
                <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-lg border border-red-200 px-5 text-sm font-bold text-red-600 transition-colors hover:bg-red-50">Hapus</button>
            </form>
        </div>
    </div>
</div>
@endsection
