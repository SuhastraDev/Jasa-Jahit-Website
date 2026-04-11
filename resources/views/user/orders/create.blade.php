@extends('layouts.user')
@section('page-title', 'Buat Pesanan')
@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6">
        <a href="{{ route('user.orders.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Pesanan Saya
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Buat Pesanan Baru</h1>
        <p class="text-gray-500 text-sm mt-1">Ikuti langkah-langkah berikut untuk membuat pesanan jahit.</p>
    </div>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-2xl px-5 py-4 mb-6">
            <p class="text-sm font-bold text-red-700 mb-2">Ada kesalahan pada form:</p>
            <ul class="list-disc list-inside text-sm text-red-600 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div x-data="{
        step: 1,
        selectedService: {{ old('service_id') ? old('service_id') : 'null' }},
        serviceName: '{{ old('service_id') ? $services->find(old('service_id'))?->name : '' }}',
        catalogs: {{ Js::from($catalogs) }},
        sizeMethod: '{{ old('measurement_id') ? 'cv' : (old('manual_chest') ? 'manual' : 'cv') }}',
        selectedMeasurementId: '{{ old('measurement_id', $measurements->first()?->id ?? '') }}',
        selectedStdSize: '',
        stdSizes: {
            'S':    { chest: 88,  waist: 72, hips: 90,  shoulder_width: 41, arm_length: 57, height: 160 },
            'M':    { chest: 92,  waist: 76, hips: 94,  shoulder_width: 43, arm_length: 58, height: 165 },
            'L':    { chest: 96,  waist: 80, hips: 98,  shoulder_width: 45, arm_length: 59, height: 168 },
            'XL':   { chest: 100, waist: 84, hips: 102, shoulder_width: 47, arm_length: 60, height: 170 },
            'XXL':  { chest: 104, waist: 88, hips: 106, shoulder_width: 49, arm_length: 61, height: 173 },
            'XXXL': { chest: 108, waist: 92, hips: 110, shoulder_width: 51, arm_length: 62, height: 175 },
        },
        applyStdSize(size) {
            this.selectedStdSize = size;
            const s = this.stdSizes[size];
            if (!s) return;
            this.$nextTick(() => {
                ['chest','waist','hips','shoulder_width','arm_length','height'].forEach(f => {
                    const el = document.getElementById('manual_' + f);
                    if (el) { el.value = s[f]; el.dispatchEvent(new Event('input')); }
                });
            });
        },

        filteredCatalogs() {
            if (!this.selectedService) return [];
            return this.catalogs.filter(c => c.service_id == this.selectedService);
        },
        isCustom() {
            return this.serviceName.toLowerCase().includes('custom');
        },
        selectService(id, name) {
            this.selectedService = id;
            this.serviceName = name;
            this.step = 2;
            this.$nextTick(() => {
                document.getElementById('step2')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    }">

        {{-- ── STEP 1: Pilih Layanan ── --}}
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                     :class="step >= 1 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500'">1</div>
                <h2 class="text-base font-bold text-gray-800">Pilih Jenis Layanan</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($services as $service)
                <div @click="selectService({{ $service->id }}, '{{ addslashes($service->name) }}')"
                     :class="selectedService == {{ $service->id }}
                        ? 'ring-2 ring-blue-500 border-blue-400 bg-blue-50'
                        : 'border-gray-200 hover:border-blue-300 hover:shadow-md bg-white'"
                     class="cursor-pointer rounded-2xl border-2 p-5 transition-all duration-200 shadow-sm">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
                        </div>
                        <div :class="selectedService == {{ $service->id }} ? 'bg-blue-600 border-blue-600' : 'bg-white border-gray-300'"
                             class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors flex-shrink-0">
                            <svg x-show="selectedService == {{ $service->id }}" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                    <h3 class="font-bold text-gray-900 mb-1.5">{{ $service->name }}</h3>
                    <p class="text-gray-500 text-xs leading-relaxed mb-3">{{ Str::limit($service->description, 70) }}</p>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-blue-600 font-bold">Mulai Rp {{ number_format($service->base_price, 0, ',', '.') }}</span>
                        <span class="text-gray-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            ~{{ $service->estimated_days }} hari
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── STEP 2: Form Detail ── --}}
        <div id="step2" x-show="selectedService" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0">

            <form action="{{ route('user.orders.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <input type="hidden" name="service_id" x-bind:value="selectedService">

                {{-- ── A: Detail Pakaian ── --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-50 bg-gray-50/40">
                        <div class="w-7 h-7 bg-blue-600 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">2</div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-800">Detail Pakaian</h2>
                            <p class="text-xs text-gray-400">Isi detail jenis dan spesifikasi pakaian yang ingin dijahit.</p>
                        </div>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">

                        {{-- Katalog --}}
                        <div x-show="filteredCatalogs().length > 0" x-transition class="sm:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Pilih dari Katalog <span class="text-gray-400 font-normal text-xs">(opsional)</span></label>
                            <select name="catalog_id" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">— Tidak memilih dari katalog —</option>
                                <template x-for="cat in filteredCatalogs()" :key="cat.id">
                                    <option :value="cat.id" x-text="cat.name" :selected="cat.id == {{ old('catalog_id', 0) }}"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Jenis Pakaian --}}
                        <div class="sm:col-span-2">
                            <label for="clothing_type" class="block text-sm font-semibold text-gray-700 mb-1.5">Jenis Pakaian <span class="text-red-500">*</span></label>
                            <div class="flex flex-wrap gap-2 mb-2">
                                @foreach(['Kemeja', 'Gaun', 'Blazer', 'Celana', 'Rok', 'Batik', 'Kebaya', 'Jas', 'Gamis', 'Lainnya'] as $type)
                                <button type="button"
                                        @click="$el.closest('.sm\\:col-span-2').querySelector('#clothing_type').value = '{{ $type }}'; $el.closest('.flex').querySelectorAll('button').forEach(b => b.classList.remove('bg-blue-600','text-white','border-blue-600')); $el.classList.add('bg-blue-600','text-white','border-blue-600');"
                                        class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-semibold text-gray-600 hover:border-blue-400 hover:text-blue-600 transition-colors {{ old('clothing_type') === $type ? 'bg-blue-600 text-white border-blue-600' : '' }}">
                                    {{ $type }}
                                </button>
                                @endforeach
                            </div>
                            <input type="text" name="clothing_type" id="clothing_type"
                                   value="{{ old('clothing_type') }}"
                                   class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm @error('clothing_type') border-red-400 @enderror"
                                   placeholder="Atau ketik manual, contoh: Baju Koko, Dress Pesta...">
                            @error('clothing_type')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Warna --}}
                        <div>
                            <label for="color" class="block text-sm font-semibold text-gray-700 mb-1.5">Warna</label>
                            <div class="flex items-center gap-2">
                                <input type="color" id="colorPicker" value="#3b82f6"
                                       @input="document.getElementById('color').value = $event.target.value; document.getElementById('colorPreview').style.backgroundColor = $event.target.value;"
                                       class="w-10 h-10 rounded-lg border border-gray-200 cursor-pointer p-0.5 flex-shrink-0">
                                <input type="text" name="color" id="color"
                                       value="{{ old('color') }}"
                                       class="flex-1 rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm"
                                       placeholder="Misal: Merah marun, Navy blue, Putih gading...">
                            </div>
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                @foreach(['Hitam','Putih','Navy','Merah','Abu-abu','Coklat','Hijau','Kuning','Pink','Biru'] as $col)
                                <button type="button"
                                        @click="document.getElementById('color').value = '{{ $col }}'"
                                        class="px-2.5 py-1 rounded-lg border border-gray-200 text-xs text-gray-600 hover:border-blue-400 hover:text-blue-600 transition-colors">
                                    {{ $col }}
                                </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Bahan --}}
                        <div>
                            <label for="material" class="block text-sm font-semibold text-gray-700 mb-1.5">Bahan / Material</label>
                            <input type="text" name="material" id="material"
                                   value="{{ old('material') }}"
                                   class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm"
                                   placeholder="Misal: Katun, Linen, Silk, Brokat...">
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                @foreach(['Katun','Linen','Silk','Sifon','Brokat','Jersey','Denim','Polyester'] as $mat)
                                <button type="button"
                                        @click="document.getElementById('material').value = '{{ $mat }}'"
                                        class="px-2.5 py-1 rounded-lg border border-gray-200 text-xs text-gray-600 hover:border-blue-400 hover:text-blue-600 transition-colors">
                                    {{ $mat }}
                                </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Catatan / Deskripsi --}}
                        <div class="sm:col-span-2">
                            <label for="description" class="block text-sm font-semibold text-gray-700 mb-1.5">Catatan Tambahan <span class="text-gray-400 font-normal text-xs">(opsional)</span></label>
                            <textarea name="description" id="description" rows="3"
                                class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="Tuliskan detail khusus: model kerah, panjang lengan, motif, aksesoris, dll.">{{ old('description') }}</textarea>
                        </div>

                        {{-- Foto Referensi --}}
                        <div class="sm:col-span-2" x-data="{ preview: null }">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Foto Referensi <span class="text-gray-400 font-normal text-xs">(opsional)</span></label>
                            <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 hover:border-blue-300 transition-colors cursor-pointer"
                                 @click="$refs.refImg.click()">
                                <input type="file" name="reference_image" x-ref="refImg" accept="image/*"
                                    @change="preview = URL.createObjectURL($event.target.files[0])"
                                    class="hidden">
                                <div x-show="!preview" class="text-center py-4">
                                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <p class="text-sm text-gray-500">Klik untuk upload foto referensi desain</p>
                                    <p class="text-xs text-gray-400 mt-0.5">JPG, PNG, WEBP — Maks 2MB</p>
                                </div>
                                <div x-show="preview" class="flex items-center gap-3">
                                    <img :src="preview" class="h-16 w-16 object-cover rounded-xl border border-gray-200 flex-shrink-0">
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">Foto dipilih</p>
                                        <button type="button" @click.stop="preview = null; $refs.refImg.value = ''" class="text-xs text-red-500 hover:text-red-700 mt-0.5">Hapus</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── B: Ukuran Badan (Custom only) ── --}}
                <div x-show="isCustom()" x-transition class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-50 bg-gray-50/40">
                        <div class="w-7 h-7 bg-purple-600 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">3</div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-800">Data Ukuran Badan <span class="text-red-500">*</span></h2>
                            <p class="text-xs text-gray-400">Diperlukan untuk layanan Custom agar jahitan pas.</p>
                        </div>
                    </div>
                    <div class="p-6">

                        {{-- Pilih metode --}}
                        <div class="flex gap-3 mb-5">
                            <button type="button" @click="sizeMethod = 'cv'"
                                    :class="sizeMethod === 'cv' ? 'bg-purple-600 text-white border-purple-600' : 'bg-white text-gray-600 border-gray-200 hover:border-purple-300'"
                                    class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 font-semibold text-sm transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Dari Foto (CV)
                            </button>
                            <button type="button" @click="sizeMethod = 'manual'"
                                    :class="sizeMethod === 'manual' ? 'bg-purple-600 text-white border-purple-600' : 'bg-white text-gray-600 border-gray-200 hover:border-purple-300'"
                                    class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl border-2 font-semibold text-sm transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Isi Manual
                            </button>
                        </div>

                        {{-- Single hidden measurement_id - nilai dikontrol Alpine --}}
                        <input type="hidden" name="measurement_id" :value="sizeMethod === 'cv' ? selectedMeasurementId : ''">

                        {{-- Opsi CV --}}
                        <div x-show="sizeMethod === 'cv'" x-transition x-cloak>
                            @if($measurements->isEmpty())
                                <div class="bg-purple-50 border border-purple-200 rounded-xl p-5 text-center">
                                    <div class="w-12 h-12 bg-purple-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </div>
                                    <p class="font-bold text-purple-800 mb-1">Belum ada data ukuran dari foto</p>
                                    <p class="text-xs text-purple-600 mb-4">Anda bisa ukur badan via foto AI, atau pilih "Isi Manual" untuk memasukkan langsung.</p>
                                    <div class="flex gap-3 justify-center">
                                        <a href="{{ route('user.measurement.index') }}" target="_blank"
                                           class="inline-flex items-center gap-1.5 px-4 py-2 bg-purple-600 text-white text-xs font-bold rounded-xl hover:bg-purple-700 transition-colors">
                                            Ukur via Foto AI
                                        </a>
                                        <button type="button" @click="sizeMethod = 'manual'"
                                                class="inline-flex items-center gap-1.5 px-4 py-2 border border-purple-300 text-purple-700 text-xs font-bold rounded-xl hover:bg-purple-50 transition-colors">
                                            Isi Manual Saja
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div class="space-y-3">
                                    @error('measurement_id')<p class="text-xs text-red-600 mb-2">{{ $message }}</p>@enderror
                                    @foreach($measurements as $m)
                                    <label @click="selectedMeasurementId = '{{ $m->id }}'"
                                           :class="selectedMeasurementId == '{{ $m->id }}' ? 'border-purple-500 bg-purple-50' : 'border-gray-200 hover:border-purple-300'"
                                           class="flex items-start gap-3 p-4 border-2 rounded-xl cursor-pointer transition-all">
                                        <div :class="selectedMeasurementId == '{{ $m->id }}' ? 'bg-purple-600 border-purple-600' : 'bg-white border-gray-300'"
                                             class="w-5 h-5 rounded-full border-2 flex items-center justify-center mt-0.5 flex-shrink-0 transition-colors">
                                            <div x-show="selectedMeasurementId == '{{ $m->id }}'" class="w-2 h-2 rounded-full bg-white"></div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                <span class="text-xs font-bold text-gray-700">{{ $m->created_at->format('d M Y, H:i') }}</span>
                                                <span class="text-xs text-gray-400">· {{ $m->ref_object_label }}</span>
                                                @if($m->is_edited)
                                                    <span class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-medium">Diedit manual</span>
                                                @endif
                                                @if($loop->first)
                                                    <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded font-medium">Terbaru</span>
                                                @endif
                                            </div>
                                            <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 mt-2">
                                                @foreach([['Dada',$m->chest],['Pinggang',$m->waist],['Pinggul',$m->hips],['Bahu',$m->shoulder_width],['Lengan',$m->arm_length],['Tinggi',$m->height]] as [$lbl,$val])
                                                <div class="text-center bg-white rounded-lg p-1.5 border border-gray-100">
                                                    <div class="text-[10px] text-gray-400">{{ $lbl }}</div>
                                                    <div class="text-xs font-bold text-gray-800">{{ $val }}<span class="font-normal text-gray-400">cm</span></div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                                <p class="text-xs text-gray-400 mt-3 text-center">
                                    Ingin data baru? <a href="{{ route('user.measurement.index') }}" target="_blank" class="text-purple-600 font-semibold hover:underline">Ukur badan via foto AI</a>
                                </p>
                            @endif
                        </div>

                        {{-- Opsi Manual --}}
                        <div x-show="sizeMethod === 'manual'" x-transition x-cloak>

                            {{-- Pilihan Ukuran Standar --}}
                            <div class="mb-4">
                                <p class="text-xs font-semibold text-gray-600 mb-2">Pilih Ukuran Standar <span class="text-gray-400 font-normal">(opsional — otomatis isi cm, bisa diedit)</span></p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(['S','M','L','XL','XXL','XXXL'] as $sz)
                                    <button type="button"
                                            @click="applyStdSize('{{ $sz }}')"
                                            :class="selectedStdSize === '{{ $sz }}'
                                                ? 'bg-purple-600 text-white border-purple-600 ring-2 ring-purple-300'
                                                : 'bg-white text-gray-700 border-gray-300 hover:border-purple-400 hover:text-purple-700'"
                                            class="px-4 py-1.5 rounded-lg border text-sm font-bold transition-all">
                                        {{ $sz }}
                                    </button>
                                    @endforeach
                                    <button type="button"
                                            @click="selectedStdSize = ''; ['manual_chest','manual_waist','manual_hips','manual_shoulder_width','manual_arm_length','manual_height'].forEach(id => { const el = document.getElementById(id); if(el) el.value=''; })"
                                            x-show="selectedStdSize !== ''"
                                            class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs text-gray-400 hover:text-red-500 hover:border-red-300 transition-all">
                                        Reset
                                    </button>
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1.5">Ukuran standar Indonesia. Nilai cm bisa Anda ubah sesuai kebutuhan.</p>
                            </div>

                            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4 flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                <p class="text-xs text-amber-800">Nilai di bawah otomatis terisi dari pilihan ukuran di atas. Anda bisa ubah manual jika perlu. Dada & Pinggang wajib diisi.</p>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                @foreach([
                                    ['manual_chest','chest','Lingkar Dada','cm','required'],
                                    ['manual_waist','waist','Lingkar Pinggang','cm','required'],
                                    ['manual_hips','hips','Lingkar Pinggul','cm',''],
                                    ['manual_shoulder_width','shoulder_width','Lebar Bahu','cm',''],
                                    ['manual_arm_length','arm_length','Panjang Lengan','cm',''],
                                    ['manual_height','height','Tinggi Badan','cm',''],
                                ] as [$name,$key,$label,$unit,$req])
                                <div class="bg-gray-50 rounded-xl p-3">
                                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                                        {{ $label }} @if($req)<span class="text-red-500">*</span>@else<span class="text-gray-400">(opsional)</span>@endif
                                    </label>
                                    <div class="flex items-center gap-1.5">
                                        <input type="number" step="0.5" name="{{ $name }}" id="{{ $name }}" value="{{ old($name) }}"
                                               @input="if(selectedStdSize && $el.value != stdSizes[selectedStdSize]?.{{ $key }}) selectedStdSize = ''"
                                               class="flex-1 min-w-0 bg-white rounded-lg border-gray-200 text-sm font-semibold focus:border-purple-500 focus:ring-purple-500"
                                               placeholder="0">
                                        <span class="text-xs text-gray-400 flex-shrink-0">{{ $unit }}</span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── C: Alamat Pengiriman ── --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
                     x-data="{
                        provinces: [],
                        cities: [],
                        districts: [],
                        villages: [],
                        loadingCities: false,
                        loadingDistricts: false,
                        loadingVillages: false,
                        province: '',
                        city: '',
                        district: '',
                        village: '',
                        detailAddr: '{{ old('detail_address', auth()->user()->address ?? '') }}',
                        rt: '{{ old('rt') }}',
                        rw: '{{ old('rw') }}',
                        postalCode: '{{ old('postal_code') }}',

                        get previewAddress() {
                            return [
                                this.detailAddr,
                                (this.rt ? 'RT ' + this.rt : '') + (this.rw ? '/RW ' + this.rw : ''),
                                this.village, this.district, this.city, this.province, this.postalCode
                            ].filter(v => v && v.trim()).join(', ');
                        },

                        async loadProvinces() {
                            try {
                                const r = await fetch('https://www.emsifa.com/api-wilayah-indonesia/api/provinces.json');
                                this.provinces = await r.json();
                            } catch(e) { console.error('Failed to load provinces', e); }
                        },
                        async onProvinceChange(e) {
                            const id = e.target.value;
                            const name = id ? e.target.options[e.target.selectedIndex].text : '';
                            this.province = name;
                            this.city = ''; this.district = ''; this.village = '';
                            this.cities = []; this.districts = []; this.villages = [];
                            if (!id) return;
                            this.loadingCities = true;
                            try {
                                const r = await fetch('https://www.emsifa.com/api-wilayah-indonesia/api/regencies/' + id + '.json');
                                this.cities = await r.json();
                            } finally { this.loadingCities = false; }
                        },
                        async onCityChange(e) {
                            const id = e.target.value;
                            const name = id ? e.target.options[e.target.selectedIndex].text : '';
                            this.city = name;
                            this.district = ''; this.village = '';
                            this.districts = []; this.villages = [];
                            if (!id) return;
                            this.loadingDistricts = true;
                            try {
                                const r = await fetch('https://www.emsifa.com/api-wilayah-indonesia/api/districts/' + id + '.json');
                                this.districts = await r.json();
                            } finally { this.loadingDistricts = false; }
                        },
                        async onDistrictChange(e) {
                            const id = e.target.value;
                            const name = id ? e.target.options[e.target.selectedIndex].text : '';
                            this.district = name;
                            this.village = '';
                            this.villages = [];
                            if (!id) return;
                            this.loadingVillages = true;
                            try {
                                const r = await fetch('https://www.emsifa.com/api-wilayah-indonesia/api/villages/' + id + '.json');
                                this.villages = await r.json();
                            } finally { this.loadingVillages = false; }
                        },
                        onVillageChange(e) {
                            const id = e.target.value;
                            this.village = id ? e.target.options[e.target.selectedIndex].text : '';
                        },
                        init() { this.loadProvinces(); }
                     }">
                    <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-50 bg-gray-50/40">
                        <div class="w-7 h-7 bg-green-600 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" x-text="isCustom() ? '4' : '3'"></div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-800">Alamat Pengiriman <span class="text-red-500">*</span></h2>
                            <p class="text-xs text-gray-400">Alamat lengkap untuk pengiriman hasil jahitan.</p>
                        </div>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Hidden inputs yang dikirim ke server --}}
                        <input type="hidden" name="province" :value="province">
                        <input type="hidden" name="city" :value="city">
                        <input type="hidden" name="district" :value="district">
                        <input type="hidden" name="village" :value="village">

                        {{-- Provinsi --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Provinsi <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <select @change="onProvinceChange($event)"
                                        class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm @error('province') border-red-400 @enderror">
                                    <option value="">— Pilih Provinsi —</option>
                                    <template x-for="p in provinces" :key="p.id">
                                        <option :value="p.id" x-text="p.name"></option>
                                    </template>
                                </select>
                                <div x-show="provinces.length === 0" class="absolute right-10 top-1/2 -translate-y-1/2" x-cloak>
                                    <svg class="animate-spin w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                </div>
                            </div>
                            @error('province')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Kota/Kabupaten --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kota / Kabupaten <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <select @change="onCityChange($event)"
                                        :disabled="cities.length === 0"
                                        class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm disabled:bg-gray-50 disabled:text-gray-400 @error('city') border-red-400 @enderror">
                                    <option value="" x-text="cities.length === 0 ? (province ? 'Memuat...' : '— Pilih provinsi dulu —') : '— Pilih Kota/Kabupaten —'"></option>
                                    <template x-for="c in cities" :key="c.id">
                                        <option :value="c.id" x-text="c.name"></option>
                                    </template>
                                </select>
                                <div x-show="loadingCities" class="absolute right-10 top-1/2 -translate-y-1/2" x-cloak>
                                    <svg class="animate-spin w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                </div>
                            </div>
                            @error('city')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Kecamatan --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kecamatan <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <select @change="onDistrictChange($event)"
                                        :disabled="districts.length === 0"
                                        class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm disabled:bg-gray-50 disabled:text-gray-400 @error('district') border-red-400 @enderror">
                                    <option value="" x-text="districts.length === 0 ? (city ? 'Memuat...' : '— Pilih kota dulu —') : '— Pilih Kecamatan —'"></option>
                                    <template x-for="d in districts" :key="d.id">
                                        <option :value="d.id" x-text="d.name"></option>
                                    </template>
                                </select>
                                <div x-show="loadingDistricts" class="absolute right-10 top-1/2 -translate-y-1/2" x-cloak>
                                    <svg class="animate-spin w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                </div>
                            </div>
                            @error('district')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- Kelurahan/Desa --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kelurahan / Desa <span class="text-gray-400 font-normal text-xs">(opsional)</span></label>
                            <div class="relative">
                                <select @change="onVillageChange($event)"
                                        :disabled="villages.length === 0"
                                        class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm disabled:bg-gray-50 disabled:text-gray-400">
                                    <option value="" x-text="villages.length === 0 ? (district ? 'Memuat...' : '— Pilih kecamatan dulu —') : '— Pilih Kelurahan/Desa —'"></option>
                                    <template x-for="v in villages" :key="v.id">
                                        <option :value="v.id" x-text="v.name"></option>
                                    </template>
                                </select>
                                <div x-show="loadingVillages" class="absolute right-10 top-1/2 -translate-y-1/2" x-cloak>
                                    <svg class="animate-spin w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                </div>
                            </div>
                        </div>

                        {{-- RT / RW / Kode Pos --}}
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">RT</label>
                                <input type="text" name="rt" x-model="rt" maxlength="5"
                                       class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm text-center" placeholder="001">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">RW</label>
                                <input type="text" name="rw" x-model="rw" maxlength="5"
                                       class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm text-center" placeholder="005">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Kode Pos</label>
                                <input type="text" name="postal_code" x-model="postalCode" maxlength="10"
                                       class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm text-center" placeholder="12345">
                            </div>
                        </div>

                        {{-- Detail Alamat --}}
                        <div class="sm:col-span-2">
                            <label for="detail_address" class="block text-sm font-semibold text-gray-700 mb-1.5">
                                Detail Alamat <span class="text-red-500">*</span>
                                <span class="text-gray-400 font-normal text-xs ml-1">Nomor rumah, nama jalan / gang, dll.</span>
                            </label>
                            <textarea name="detail_address" id="detail_address" rows="2" x-model="detailAddr"
                                class="block w-full rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm @error('detail_address') border-red-400 @enderror"
                                placeholder="Contoh: Jl. Melati No. 12, Gang Anggrek, dekat Masjid Al-Ikhlas">{{ old('detail_address', auth()->user()->address ?? '') }}</textarea>
                            @error('detail_address')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>

                        {{-- No HP Penerima --}}
                        <div class="sm:col-span-2">
                            <label for="recipient_phone" class="block text-sm font-semibold text-gray-700 mb-1.5">
                                No. HP Penerima
                                <span class="text-gray-400 font-normal text-xs ml-1">(untuk kurir — isi jika berbeda dengan nomor akun)</span>
                            </label>
                            <input type="text" name="recipient_phone" id="recipient_phone"
                                   value="{{ old('recipient_phone', auth()->user()->phone) }}"
                                   class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm"
                                   placeholder="Contoh: 08123456789">
                        </div>

                        {{-- Preview Alamat --}}
                        <div class="sm:col-span-2" x-show="province || city" x-cloak>
                            <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3">
                                <p class="text-xs font-semibold text-green-700 mb-1 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                    Alamat Lengkap:
                                </p>
                                <p class="text-sm text-green-800 leading-relaxed" x-text="previewAddress || '—'"></p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Submit ── --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-gray-500">
                        <span class="font-semibold text-gray-700">Pastikan semua data sudah benar</span> sebelum mengirim pesanan.
                    </div>
                    <div class="flex gap-3 flex-shrink-0">
                        <a href="{{ route('user.orders.index') }}" class="px-5 py-2.5 text-sm text-gray-500 hover:text-gray-700 font-semibold border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                            Batal
                        </a>
                        <button type="submit"
                            class="px-8 py-2.5 bg-blue-600 text-white rounded-xl font-bold text-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Buat Pesanan
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection
