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
        $allMeasurementFields = [
            'neck', 'chest', 'waist', 'hips', 'shoulder_width', 'shirt_length',
            'arm_length', 'upper_arm', 'wrist', 'height', 'pants_waist', 'pants_hips',
            'thigh', 'knee', 'calf', 'ankle', 'inseam', 'outseam', 'rise',
        ];
        $visibleMeasurementFields = collect($measurementSections)
            ->flatMap(fn ($section) => collect($section['fields'])->pluck(1))
            ->all();
        // Only show a garment section (Baju/Celana/Rok) if at least one of
        // its fields actually has a value - e.g. uploading just a shirt
        // photo shouldn't render empty "Ukuran Celana"/"Ukuran Rok" blocks.
        $visibleSections = collect($measurementSections)->filter(
            fn ($section) => collect($section['fields'])->contains(fn ($f) => isset($data[$f[1]]) && is_numeric($data[$f[1]]))
        )->values();
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

    @if(!empty($interactiveOverlays))
        <div class="mb-8">
            <p class="mb-1 text-xs font-black uppercase tracking-wide text-slate-500">Visual Deteksi — Titik &amp; Garis Ukur</p>
            <p class="mb-3 text-xs text-slate-500">Seret titik biru untuk mengoreksi posisinya — ukuran cm ikut dihitung ulang otomatis dan tersinkron ke kolom di bawah. Titik abu-abu &amp; garis putus-putus mengikuti titik lain, tidak bisa digeser sendiri.</p>
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach($interactiveOverlays as $label => $overlayData)
                    @php
                        $geometry = $overlayData['geometry'];
                        $slug = \Illuminate\Support\Str::slug($label);
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
                    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm" x-data="garmentOverlay(@js($geometry), @js($label), @js($slug))" x-init="init($el)">
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 bg-slate-50 px-3 py-2">
                            <span class="text-xs font-bold text-slate-600">{{ $label }}</span>
                            <div class="flex items-center gap-1.5">
                                <button type="button" @click="toggleAddMode()"
                                    :class="addMode ? 'bg-blue-600 text-white' : 'text-blue-600 hover:bg-blue-50'"
                                    class="rounded-md px-2 py-1 text-[11px] font-bold transition-colors"
                                    x-text="addMode ? 'Selesai Menambah' : '+ Tambah Ukuran'"></button>
                                <button type="button" @click="resetPoints()" class="rounded-md px-2 py-1 text-[11px] font-bold text-blue-600 hover:bg-blue-50">Reset titik</button>
                            </div>
                        </div>
                        <p x-show="addMode" x-cloak class="border-b border-blue-100 bg-blue-50 px-3 py-1.5 text-[11px] font-semibold text-blue-700">Klik 2 titik di foto untuk membuat garis ukur baru.</p>
                        <div class="relative touch-none" :class="addMode ? 'cursor-crosshair' : ''" @click="onSvgClick($event)"
                            @mousemove="const r = $el.getBoundingClientRect(); tx = $event.clientX - r.left; ty = $event.clientY - r.top;">
                            <img src="{{ $overlayData['photo_url'] ?? asset('storage/' . $overlayData['photo_path']) }}" alt="Deteksi {{ $label }}" class="block w-full select-none" draggable="false">
                            {{-- Points/lines are rendered statically here (not via Alpine x-for) -
                                 <template x-for> inside <svg> silently breaks: browsers parse the
                                 template's content in the HTML namespace, so cloned <circle>/<line>
                                 elements come out as non-SVG nodes with cx/cy/fill never applied.
                                 The JS below finds these by id and updates their attributes
                                 imperatively on drag instead. Custom (user-added) points/lines are
                                 built the same imperative way via createElementNS, see garmentOverlay().
                                 :class/@click deliberately live on this wrapping <div>, not the <svg>
                                 itself - Alpine binding those directly on an <svg> element throws
                                 internally (confirmed by hand in a browser), same family of issue as
                                 the x-for-in-svg namespace bug above. --}}
                            <svg x-ref="svg" viewBox="0 0 {{ $geometry['image_width'] }} {{ $geometry['image_height'] }}" preserveAspectRatio="xMidYMid meet"
                                class="absolute inset-0 h-full w-full"
                                @pointermove.window="onDrag($event)" @pointerup.window="endDrag()" @pointercancel.window="endDrag()">
                                @foreach($geometry['lines'] as $line)
                                    @php
                                        $pa = $resolvePoint($line['point_ids'][0]);
                                        $pb = $resolvePoint($line['point_ids'][1]);
                                    @endphp
                                    <line id="ov-{{ $slug }}-line-{{ $line['field'] }}"
                                        x1="{{ $pa['x'] }}" y1="{{ $pa['y'] }}" x2="{{ $pb['x'] }}" y2="{{ $pb['y'] }}"
                                        stroke="{{ $line['draggable'] ? '#f97316' : '#94a3b8' }}" stroke-width="5" stroke-linecap="round"
                                        @if(!$line['draggable']) stroke-dasharray="6 5" @endif
                                        opacity="{{ $line['draggable'] ? '0.75' : '0.55' }}"
                                        class="cursor-pointer transition-opacity hover:opacity-100"
                                        @mouseenter="tip = lineTooltip('{{ $line['field'] }}')"
                                        @mouseleave="tip = null"></line>
                                @endforeach
                                @foreach($geometry['points'] as $point)
                                    <circle id="ov-{{ $slug }}-point-{{ $point['id'] }}"
                                        cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="11"
                                        fill="{{ $point['draggable'] ? '#2563eb' : '#94a3b8' }}" stroke="white" stroke-width="3"
                                        style="touch-action: none;"
                                        class="{{ $point['draggable'] ? 'cursor-grab active:cursor-grabbing transition-opacity hover:opacity-80' : 'cursor-default' }}"
                                        @pointerdown="startDrag('{{ $point['id'] }}', $event)"
                                        @mouseenter="tip = pointTooltip('{{ $point['id'] }}')"
                                        @mouseleave="tip = null"></circle>
                                @endforeach
                            </svg>
                            <div x-show="tip" x-text="tip" x-cloak
                                class="pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-full rounded-md bg-slate-900 px-2.5 py-1.5 text-xs font-bold text-white shadow-lg"
                                :style="`left:${tx}px; top:${ty - 10}px`"></div>
                            {{-- Right-click menu for a custom line: klik kanan garis tambahan -> Edit Nama / Hapus. --}}
                            <div x-show="contextMenu.visible" x-cloak @click.outside="contextMenu.visible = false"
                                class="absolute z-20 min-w-[9rem] rounded-lg border border-slate-200 bg-white py-1 text-xs font-bold text-slate-700 shadow-lg"
                                :style="`left:${contextMenu.x}px; top:${contextMenu.y}px`">
                                <button type="button" @click="editCustomLabel(contextMenu.field); contextMenu.visible = false" class="block w-full px-3 py-1.5 text-left hover:bg-slate-50">Edit Nama Label</button>
                                <button type="button" @click="removeCustomLine(contextMenu.field); contextMenu.visible = false" class="block w-full px-3 py-1.5 text-left text-red-600 hover:bg-red-50">Hapus Ukuran Ini</button>
                            </div>
                            {{-- Inline visual label editor for a custom line - shown right on
                                 top of the photo at the line's midpoint, replacing a native
                                 prompt() dialog. --}}
                            <div x-show="editingField" x-cloak
                                class="absolute z-20 -translate-x-1/2 -translate-y-1/2"
                                :style="`left:${editX}px; top:${editY}px`">
                                <input type="text" x-ref="labelEditInput" x-model="editingValue"
                                    @keydown.enter="confirmLabelEdit()" @keydown.escape="cancelLabelEdit()" @blur="confirmLabelEdit()"
                                    @click.stop
                                    class="w-40 rounded-md border-2 border-violet-500 bg-white px-2 py-1 text-center text-xs font-black text-violet-700 shadow-lg focus:outline-none">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @elseif(!empty($debugImages))
        <div class="mb-8">
            <p class="mb-3 text-xs font-black uppercase tracking-wide text-slate-500">Visual Deteksi — Kontur &amp; Titik Ukur</p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach($debugImages as $label => $path)
                    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-600">{{ $label }}</div>
                        <img src="{{ asset('storage/' . $path) }}" alt="Deteksi {{ $label }}" class="w-full">
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <form action="{{ route('user.measurement.store') }}" method="POST" x-data="{ edited: false }" @input="edited = true">
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
        <input type="hidden" id="garment-overlays-json-input" name="garment_overlays_json" value='@json($interactiveOverlays ?? [])'>
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
            @foreach($visibleSections as $section)
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

        @if(!empty($interactiveOverlays))
            <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2 lg:gap-8">
                @foreach($interactiveOverlays as $label => $overlayData)
                    {{-- Populated purely by renderCustomFieldsSection() in JS when the
                         user adds a custom line via "+ Tambah Ukuran" on this card's
                         photo - empty/hidden until then. --}}
                    <div id="custom-fields-{{ \Illuminate\Support\Str::slug($label) }}" class="hidden" data-garment-label="{{ $label }}"></div>
                @endforeach
            </div>
        @endif

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

@push('scripts')
<script>
    // A midpoint point (waist_mid, hem_mid) has no state of its own - it's
    // always the average of its two source points, resolved recursively so
    // it stays in sync with wherever the sources currently are.
    function resolveOverlayPoint(points, meta, id) {
        const info = meta[id];
        if (info.derived_from) {
            const [aId, bId] = info.derived_from;
            const a = resolveOverlayPoint(points, meta, aId);
            const b = resolveOverlayPoint(points, meta, bId);
            return { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };
        }
        return points[id];
    }

    // Mirrors the px->cm + circumference-doubling convention from
    // garment_measurement.py's pixel_to_cm()/multiplier so a dragged point
    // produces the exact same number the server would have computed at
    // that pixel distance - never a client-side approximation.
    function computeOverlayLineValueCm(points, meta, scale, line) {
        if (!line.draggable) return line.value_cm;
        const [aId, bId] = line.point_ids;
        const a = resolveOverlayPoint(points, meta, aId);
        const b = resolveOverlayPoint(points, meta, bId);
        const distPx = Math.hypot(a.x - b.x, a.y - b.y);
        return Math.round((distPx / scale) * line.multiplier * 100) / 100;
    }

    window.__garmentOverlayState = window.__garmentOverlayState || {};

    const SVG_NS = 'http://www.w3.org/2000/svg';
    function createSvgEl(tag, attrs) {
        const el = document.createElementNS(SVG_NS, tag);
        Object.entries(attrs).forEach(([k, v]) => el.setAttribute(k, v));
        return el;
    }

    // The <circle>/<line> elements are server-rendered as plain static SVG
    // (see result.blade.php) - this component finds them by id and updates
    // their cx/cy/x1/y1/x2/y2 attributes directly on drag, rather than
    // letting Alpine re-render them (x-for inside <svg> doesn't work, see
    // the comment in the blade file). Alpine only owns the tooltip state
    // (tip/tx/ty) and the reset button here, both plain HTML. Custom
    // (user-added) points/lines are built the same imperative way via
    // createSvgEl() and registered into the exact same `points`/`meta`/
    // `lines`/`els` structures as the auto-detected ones, so drag, tooltip,
    // and save-on-submit all work identically without a parallel code path.
    function garmentOverlay(geometry, label, slug) {
        const meta = Object.fromEntries(geometry.points.map((p) => [p.id, p]));
        const initialPoints = Object.fromEntries(geometry.points.map((p) => [p.id, { x: p.x, y: p.y }]));

        return {
            label,
            scale: geometry.scale,
            meta,
            points: JSON.parse(JSON.stringify(initialPoints)),
            original: initialPoints,
            lines: [...geometry.lines],
            dragId: null,
            tip: null,
            tx: 0,
            ty: 0,
            els: { points: {}, lines: {}, labels: {} },
            addMode: false,
            pendingPointId: null,
            customCounter: 0,
            customPointCounter: 0,
            contextMenu: { visible: false, x: 0, y: 0, field: null },
            editingField: null,
            editingValue: '',
            editX: 0,
            editY: 0,

            init(root) {
                this.root = root;
                Object.keys(meta).forEach((id) => {
                    this.els.points[id] = root.querySelector('#ov-' + slug + '-point-' + CSS.escape(id));
                });
                this.lines.forEach((line) => {
                    this.els.lines[line.field] = root.querySelector('#ov-' + slug + '-line-' + CSS.escape(line.field));
                });
                window.__garmentOverlayState[this.label] = this;
                this.syncInputs();
            },
            resolvedPoint(id) {
                return resolveOverlayPoint(this.points, this.meta, id);
            },
            lineValueCm(line) {
                return computeOverlayLineValueCm(this.points, this.meta, this.scale, line);
            },
            lineByField(field) {
                return this.lines.find((l) => l.field === field);
            },
            lineTooltip(field) {
                const line = this.lineByField(field);
                const value = this.lineValueCm(line);
                return line.label + ': ' + value + ' cm' + (line.note ? ' — ' + line.note : '');
            },
            pointTooltip(id) {
                const info = this.meta[id];
                return info.label + (info.draggable ? '' : ' (mengikuti titik lain)');
            },
            viewBoxPoint(evt) {
                const svg = this.$refs.svg;
                const rect = svg.getBoundingClientRect();
                const viewBox = svg.viewBox.baseVal;
                return {
                    x: Math.round(((evt.clientX - rect.left) / rect.width) * viewBox.width),
                    y: Math.round(((evt.clientY - rect.top) / rect.height) * viewBox.height),
                };
            },
            // Inverse of viewBoxPoint: viewBox coordinates -> pixels relative
            // to the wrapping <div>, for positioning plain-HTML overlays
            // (the inline label editor) that live outside the <svg> itself.
            // The <svg> fills that div exactly (absolute inset-0), so its
            // own bounding box doubles as the wrapper-relative origin.
            viewBoxToScreen(point) {
                const svg = this.$refs.svg;
                const rect = svg.getBoundingClientRect();
                const viewBox = svg.viewBox.baseVal;
                return {
                    x: (point.x / viewBox.width) * rect.width,
                    y: (point.y / viewBox.height) * rect.height,
                };
            },
            startDrag(id, evt) {
                if (this.addMode || !this.meta[id].draggable) return;
                this.dragId = id;
                evt.preventDefault();
            },
            onDrag(evt) {
                if (!this.dragId) return;
                this.points[this.dragId] = this.viewBoxPoint(evt);
                this.render();
                this.syncInputs();
            },
            endDrag() {
                this.dragId = null;
            },
            resetPoints() {
                // Auto-detected points go back to their server position, and
                // any custom (user-added) lines/points are cleared entirely -
                // "Reset" means back to exactly what detection produced.
                [...this.lines].filter((l) => l.custom).forEach((l) => this.removeCustomLine(l.field));
                Object.keys(this.original).forEach((id) => {
                    this.points[id] = { ...this.original[id] };
                });
                this.render();
                this.syncInputs();
            },
            render() {
                Object.keys(this.meta).forEach((id) => {
                    const el = this.els.points[id];
                    if (!el) return;
                    const p = this.resolvedPoint(id);
                    el.setAttribute('cx', p.x);
                    el.setAttribute('cy', p.y);
                });
                this.lines.forEach((line) => {
                    const el = this.els.lines[line.field];
                    if (el && line.draggable) {
                        const [aId, bId] = line.point_ids;
                        const a = this.resolvedPoint(aId);
                        const b = this.resolvedPoint(bId);
                        el.setAttribute('x1', a.x);
                        el.setAttribute('y1', a.y);
                        el.setAttribute('x2', b.x);
                        el.setAttribute('y2', b.y);
                    }
                    this.positionCustomLabel(line.field);
                });
            },
            labelMidpoint(field) {
                const line = this.lineByField(field);
                const [aId, bId] = line.point_ids;
                const a = this.resolvedPoint(aId);
                const b = this.resolvedPoint(bId);
                return { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 - 10 };
            },
            positionCustomLabel(field) {
                const text = this.els.labels[field];
                if (!text) return;
                const mid = this.labelMidpoint(field);
                text.setAttribute('x', mid.x);
                text.setAttribute('y', mid.y);
                text.textContent = this.lineByField(field).label;
                if (this.editingField === field) {
                    const screen = this.viewBoxToScreen(mid);
                    this.editX = screen.x;
                    this.editY = screen.y;
                }
            },
            syncInputs() {
                this.lines.forEach((line) => {
                    if (!line.draggable) return;
                    const input = document.getElementById('measurement-' + line.field);
                    if (!input) return;
                    const value = this.lineValueCm(line);
                    if (input.value !== String(value)) {
                        input.value = value;
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
            },
            serializeGeometry() {
                return {
                    ...geometry,
                    points: Object.keys(this.meta).map((id) => ({ ...this.meta[id], ...this.resolvedPoint(id) })),
                    lines: this.lines.map((line) => ({ ...line, value_cm: this.lineValueCm(line) })),
                };
            },

            // --- Custom (user-added) measurements -----------------------

            toggleAddMode() {
                this.addMode = !this.addMode;
                this.pendingPointId = null;
            },
            onSvgClick(evt) {
                if (!this.addMode) return;
                let pointId;
                const prefix = 'ov-' + slug + '-point-';
                if (evt.target.tagName === 'circle' && evt.target.id.startsWith(prefix)) {
                    // Re-use an existing point (auto-detected or custom)
                    // instead of dropping a duplicate on top of it.
                    pointId = evt.target.id.slice(prefix.length);
                } else {
                    pointId = this.createCustomPoint(this.viewBoxPoint(evt));
                }

                if (this.pendingPointId === null) {
                    this.pendingPointId = pointId;
                    return;
                }
                if (this.pendingPointId !== pointId) {
                    this.createCustomLine(this.pendingPointId, pointId);
                }
                this.pendingPointId = null;
            },
            createCustomPoint(pos) {
                this.customPointCounter++;
                const id = 'custom_pt_' + slug + '_' + this.customPointCounter;
                this.meta[id] = { id, label: 'Titik ukur', draggable: true };
                this.points[id] = pos;
                const el = createSvgEl('circle', {
                    id: 'ov-' + slug + '-point-' + id,
                    cx: pos.x, cy: pos.y, r: 11, fill: '#7c3aed', stroke: 'white', 'stroke-width': 3,
                    style: 'touch-action: none;', class: 'cursor-grab active:cursor-grabbing transition-opacity hover:opacity-80',
                });
                el.addEventListener('pointerdown', (e) => this.startDrag(id, e));
                el.addEventListener('mouseenter', () => { this.tip = this.pointTooltip(id); });
                el.addEventListener('mouseleave', () => { this.tip = null; });
                this.$refs.svg.appendChild(el);
                this.els.points[id] = el;
                return id;
            },
            createCustomLine(idA, idB) {
                this.customCounter++;
                const field = 'custom_' + slug + '_' + this.customCounter;
                const line = {
                    field, label: 'Ukuran Baru ' + this.customCounter, value_cm: 0,
                    point_ids: [idA, idB], multiplier: 1, draggable: true, custom: true,
                };
                this.lines.push(line);

                const el = createSvgEl('line', {
                    id: 'ov-' + slug + '-line-' + field,
                    stroke: '#7c3aed', 'stroke-width': 5, 'stroke-linecap': 'round', opacity: 0.75,
                    class: 'cursor-pointer transition-opacity hover:opacity-100',
                });
                el.addEventListener('mouseenter', () => { this.tip = this.lineTooltip(field); });
                el.addEventListener('mouseleave', () => { this.tip = null; });
                el.addEventListener('dblclick', () => this.editCustomLabel(field));
                el.addEventListener('contextmenu', (e) => this.openContextMenu(field, e));
                // Insert before the first <circle> so points stay visually on top.
                this.$refs.svg.insertBefore(el, this.$refs.svg.querySelector('circle'));
                this.els.lines[field] = el;

                // Label written directly on the photo at the line's midpoint
                // (not just on hover) - only for custom lines, the
                // auto-detected ones stay hover-only to avoid cluttering the
                // photo with a dozen text labels.
                const text = createSvgEl('text', {
                    'text-anchor': 'middle', fill: '#7c3aed', 'font-size': 16, 'font-weight': 800,
                    stroke: 'white', 'stroke-width': 4, 'paint-order': 'stroke', class: 'pointer-events-none select-none',
                });
                this.$refs.svg.appendChild(text);
                this.els.labels[field] = text;

                this.render();
                this.renderCustomFieldsSection();
            },
            editCustomLabel(field) {
                const line = this.lineByField(field);
                if (!line) return;
                // A small text input placed right on the photo at the line's
                // midpoint, instead of a native prompt() dialog - visually
                // in place, not a disruptive popup.
                const screen = this.viewBoxToScreen(this.labelMidpoint(field));
                this.editX = screen.x;
                this.editY = screen.y;
                this.editingValue = line.label;
                this.editingField = field;
                this.$nextTick(() => {
                    this.$refs.labelEditInput?.focus();
                    this.$refs.labelEditInput?.select();
                });
            },
            confirmLabelEdit() {
                if (!this.editingField) return;
                const line = this.lineByField(this.editingField);
                if (line) {
                    line.label = this.editingValue.trim() || line.label;
                    this.positionCustomLabel(this.editingField);
                    this.renderCustomFieldsSection();
                }
                this.editingField = null;
            },
            cancelLabelEdit() {
                this.editingField = null;
            },
            openContextMenu(field, evt) {
                evt.preventDefault();
                const rect = this.$refs.svg.closest('.relative.touch-none').getBoundingClientRect();
                this.contextMenu = { visible: true, x: evt.clientX - rect.left, y: evt.clientY - rect.top, field };
            },
            removeCustomLine(field) {
                const line = this.lineByField(field);
                if (!line) return;
                if (this.editingField === field) this.editingField = null;
                if (this.contextMenu.field === field) this.contextMenu.visible = false;
                this.lines = this.lines.filter((l) => l.field !== field);
                this.els.lines[field]?.remove();
                this.els.labels[field]?.remove();
                delete this.els.lines[field];
                delete this.els.labels[field];

                // Drop a custom point only if no other line still uses it.
                line.point_ids.forEach((id) => {
                    if (!id.startsWith('custom_pt_')) return;
                    const stillUsed = this.lines.some((l) => l.point_ids.includes(id));
                    if (stillUsed) return;
                    this.els.points[id]?.remove();
                    delete this.els.points[id];
                    delete this.points[id];
                    delete this.meta[id];
                });

                this.renderCustomFieldsSection();
            },
            renderCustomFieldsSection() {
                const container = document.getElementById('custom-fields-' + slug);
                if (!container) return;
                const customLines = this.lines.filter((l) => l.custom);
                container.classList.toggle('hidden', customLines.length === 0);
                container.innerHTML = '';
                if (customLines.length === 0) return;

                const heading = document.createElement('div');
                heading.className = 'mb-3 flex items-center gap-2 border-b border-slate-200 pb-3';
                heading.innerHTML = '<span class="mt-1 h-10 w-1 shrink-0 rounded-full bg-violet-600"></span>'
                    + '<div><p class="text-[10px] font-black text-violet-600">' + escapeHtml(this.label.toUpperCase())
                    + '</p><h2 class="mt-0.5 text-lg font-black text-slate-950">Ukuran Tambahan</h2></div>';
                container.appendChild(heading);

                const grid = document.createElement('div');
                grid.className = 'grid grid-cols-1 gap-3 sm:grid-cols-2';
                container.appendChild(grid);

                customLines.forEach((line) => {
                    const value = this.lineValueCm(line);
                    const article = document.createElement('article');
                    article.dataset.customField = '1';
                    article.className = 'rounded-lg border border-violet-200 bg-violet-50/40 p-4 shadow-sm';
                    article.innerHTML = `
                        <div class="flex items-start justify-between gap-2">
                            <input type="text" value="${escapeHtml(line.label)}" data-role="label"
                                class="min-w-0 flex-1 border-0 border-b border-dashed border-violet-300 bg-transparent px-0 text-xs font-black leading-5 text-slate-800 focus:border-violet-500 focus:outline-none focus:ring-0">
                            <button type="button" data-role="remove" title="Hapus ukuran ini"
                                class="shrink-0 rounded-md px-1.5 py-0.5 text-xs font-black text-red-500 hover:bg-red-50">&times;</button>
                        </div>
                        <button type="button" data-role="multiplier"
                            class="mt-2 rounded-md border px-2 py-0.5 text-[9px] font-black uppercase ${line.multiplier === 2 ? 'border-violet-200 bg-violet-50 text-violet-700' : 'border-cyan-200 bg-cyan-50 text-cyan-700'}">
                            ${line.multiplier === 2 ? 'lingkar' : 'panjang'}
                        </button>
                        <div class="mt-3 flex h-11 items-center overflow-hidden rounded-lg border border-slate-200 bg-white focus-within:border-violet-500 focus-within:ring-2 focus-within:ring-violet-100">
                            <input id="measurement-${line.field}" type="number" step="0.01" min="0" max="400" name="${escapeHtml(line.field)}" value="${value}"
                                class="h-full min-w-0 flex-1 border-0 bg-transparent px-3 text-base font-black text-slate-950 focus:ring-0">
                            <span class="flex h-full items-center border-l border-slate-200 bg-slate-50 px-3 text-xs font-bold text-slate-400">cm</span>
                        </div>
                    `;

                    article.querySelector('[data-role="label"]').addEventListener('input', (e) => {
                        line.label = e.target.value || 'Ukuran Baru';
                        this.positionCustomLabel(line.field);
                    });
                    article.querySelector('[data-role="remove"]').addEventListener('click', () => this.removeCustomLine(line.field));
                    article.querySelector('[data-role="multiplier"]').addEventListener('click', () => {
                        line.multiplier = line.multiplier === 2 ? 1 : 2;
                        this.syncInputs();
                        this.renderCustomFieldsSection();
                    });

                    grid.appendChild(article);
                });
            },
        };
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    document.addEventListener('DOMContentLoaded', () => {
        const hidden = document.getElementById('garment-overlays-json-input');
        const form = hidden ? hidden.closest('form') : null;
        if (!form || !hidden) return;

        // Persist any manual point corrections into the same payload that
        // gets saved to Measurement.garment_overlays_json, so reopening a
        // saved result later (show.blade.php) reflects the corrected
        // points/values rather than the original auto-detected ones.
        form.addEventListener('submit', () => {
            let payload = {};
            try {
                payload = JSON.parse(hidden.value || '{}');
            } catch (e) {
                payload = {};
            }
            Object.entries(window.__garmentOverlayState || {}).forEach(([label, component]) => {
                if (!payload[label]) return;
                payload[label].geometry = component.serializeGeometry();
            });
            hidden.value = JSON.stringify(payload);
        });
    });
</script>
@endpush
@endsection
