@extends('layouts.user')
@section('page-title', 'Ukur Badan')
@section('content')

<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8" x-data="measureApp()">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Ukur Badan (AI)</h1>
        <p class="text-gray-500 text-sm mt-1">Upload foto dan biarkan AI menganalisis ukuran badan Anda langsung di browser — tidak perlu server tambahan.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-5">

            {{-- Contoh Foto --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-9 h-9 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <h2 class="font-bold text-gray-900">Contoh Foto yang Benar</h2>
                </div>
                <div class="flex flex-col sm:flex-row gap-6 items-start">
                    <div class="flex-shrink-0 mx-auto sm:mx-0">
                        <div class="bg-gradient-to-b from-sky-50 to-blue-50 rounded-2xl border border-blue-100 p-4 w-44 flex flex-col items-center">
                            <svg viewBox="0 0 160 300" xmlns="http://www.w3.org/2000/svg" class="w-40 h-auto">
                                <defs>
                                    <marker id="aR" markerWidth="8" markerHeight="6" refX="8" refY="3" orient="auto"><polygon points="0 0, 8 3, 0 6" fill="#ef4444"/></marker>
                                    <marker id="aL" markerWidth="8" markerHeight="6" refX="0" refY="3" orient="auto"><polygon points="8 0, 0 3, 8 6" fill="#ef4444"/></marker>
                                    <marker id="aWR" markerWidth="8" markerHeight="6" refX="8" refY="3" orient="auto"><polygon points="0 0, 8 3, 0 6" fill="#f59e0b"/></marker>
                                    <marker id="aWL" markerWidth="8" markerHeight="6" refX="0" refY="3" orient="auto"><polygon points="8 0, 0 3, 8 6" fill="#f59e0b"/></marker>
                                    <marker id="aHD" markerWidth="6" markerHeight="8" refX="3" refY="8" orient="auto"><polygon points="0 0, 6 0, 3 8" fill="#10b981"/></marker>
                                    <marker id="aHU" markerWidth="6" markerHeight="8" refX="3" refY="0" orient="auto"><polygon points="0 8, 6 8, 3 0" fill="#10b981"/></marker>
                                </defs>
                                <!-- Orang -->
                                <circle cx="75" cy="28" r="18" fill="#fde68a" stroke="#d97706" stroke-width="1.5"/>
                                <path d="M 58 22 Q 75 8 92 22" fill="#92400e"/>
                                <circle cx="69" cy="27" r="2" fill="#1e293b"/>
                                <circle cx="81" cy="27" r="2" fill="#1e293b"/>
                                <path d="M 70 34 Q 75 38 80 34" fill="none" stroke="#92400e" stroke-width="1.5" stroke-linecap="round"/>
                                <rect x="70" y="46" width="10" height="14" fill="#fde68a"/>
                                <rect x="48" y="58" width="54" height="72" rx="3" fill="#3b82f6" stroke="#2563eb" stroke-width="0.5"/>
                                <rect x="48" y="128" width="24" height="78" rx="3" fill="#1e40af"/>
                                <rect x="78" y="128" width="24" height="78" rx="3" fill="#1e40af"/>
                                <rect x="51" y="204" width="18" height="38" rx="2" fill="#1e3a8a"/>
                                <rect x="81" y="204" width="18" height="38" rx="2" fill="#1e3a8a"/>
                                <ellipse cx="60" cy="244" rx="12" ry="5" fill="#0f172a"/>
                                <ellipse cx="90" cy="244" rx="12" ry="5" fill="#0f172a"/>
                                <path d="M 48 64 L 28 108 L 23 132" fill="none" stroke="#fde68a" stroke-width="9" stroke-linecap="round"/>
                                <circle cx="22" cy="135" r="7" fill="#fde68a"/>
                                <path d="M 102 64 L 122 108 L 127 132" fill="none" stroke="#fde68a" stroke-width="9" stroke-linecap="round"/>
                                <circle cx="128" cy="135" r="7" fill="#fde68a"/>

                                <!-- Kertas A4 di samping kanan pinggang -->
                                <rect x="106" y="100" width="22" height="31" rx="2" fill="white" stroke="#6366f1" stroke-width="1.5"/>
                                <!-- Garis-garis di kertas (imitasi tulisan) -->
                                <line x1="109" y1="107" x2="125" y2="107" stroke="#a5b4fc" stroke-width="1"/>
                                <line x1="109" y1="111" x2="125" y2="111" stroke="#a5b4fc" stroke-width="1"/>
                                <line x1="109" y1="115" x2="121" y2="115" stroke="#a5b4fc" stroke-width="1"/>
                                <!-- Label A4 -->
                                <text x="117" y="123" text-anchor="middle" fill="#6366f1" font-size="6" font-weight="bold">A4</text>
                                <!-- Panah dari kertas ke tubuh -->
                                <line x1="106" y1="115" x2="102" y2="115" stroke="#6366f1" stroke-width="1" stroke-dasharray="2,1"/>
                                <!-- Label posisi -->
                                <text x="117" y="138" text-anchor="middle" fill="#6366f1" font-size="5.5">Letakkan</text>
                                <text x="117" y="144" text-anchor="middle" fill="#6366f1" font-size="5.5">di sini</text>

                                <!-- Garis DADA -->
                                <line x1="48" y1="80" x2="102" y2="80" stroke="#ef4444" stroke-width="1.5" stroke-dasharray="4,2" marker-start="url(#aL)" marker-end="url(#aR)"/>
                                <text x="75" y="76" text-anchor="middle" fill="#ef4444" font-size="7" font-weight="bold">DADA</text>
                                <!-- Garis PINGGANG -->
                                <line x1="50" y1="108" x2="100" y2="108" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="4,2" marker-start="url(#aWL)" marker-end="url(#aWR)"/>
                                <text x="75" y="104" text-anchor="middle" fill="#f59e0b" font-size="7" font-weight="bold">PINGGANG</text>
                                <!-- Garis TINGGI -->
                                <line x1="10" y1="10" x2="10" y2="244" stroke="#10b981" stroke-width="1.5" marker-start="url(#aHU)" marker-end="url(#aHD)"/>
                                <text x="10" y="132" text-anchor="middle" fill="#10b981" font-size="7" font-weight="bold" transform="rotate(-90,10,132)">TINGGI</text>
                            </svg>
                            <p class="text-[11px] text-blue-600 font-semibold text-center mt-2">Posisi Ideal</p>
                            <p class="text-[10px] text-gray-400 text-center">Tampak depan + benda referensi</p>
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-700 mb-3">Panduan agar hasil akurat:</p>
                        <ul class="space-y-2.5">
                            <li class="flex items-start gap-2.5"><div class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"><svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div><span class="text-sm text-gray-600">Berdiri tegak menghadap kamera, tangan sedikit terbuka</span></li>
                            <li class="flex items-start gap-2.5"><div class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"><svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div><span class="text-sm text-gray-600">Pakai baju yang pas/ketat, bukan baju kebesaran</span></li>
                            <li class="flex items-start gap-2.5"><div class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"><svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div><span class="text-sm text-gray-600">Seluruh badan terlihat: kepala hingga kaki</span></li>
                            <li class="flex items-start gap-2.5"><div class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"><svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div><span class="text-sm text-gray-600">Pencahayaan cukup, latar belakang kontras</span></li>
                            <li class="flex items-start gap-2.5"><div class="w-5 h-5 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"><svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div><span class="text-sm text-gray-600">Letakkan benda referensi (kertas A4 / kartu ATM) di samping pinggang</span></li>
                            <li class="flex items-start gap-2.5"><div class="w-5 h-5 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"><svg class="w-3 h-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg></div><span class="text-sm text-gray-600">Jangan miring, condong, atau menutup bagian badan</span></li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Form Upload --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-9 h-9 bg-purple-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <h2 class="font-bold text-gray-900">Analisis Ukuran AI</h2>
                </div>
                <div class="space-y-4" x-data="{ refObject: 'a4' }">
                    {{-- Benda Referensi --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Benda Referensi <span class="text-red-500">*</span></label>
                        <select x-model="refObject" @change="$dispatch('ref-changed', refObject)"
                            class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="a4">Kertas A4 (21 × 29.7 cm)</option>
                            <option value="atm">Kartu ATM / KTP (8.56 × 5.4 cm)</option>
                            <option value="custom">Custom (ukuran sendiri)</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Letakkan benda ini di samping pinggang agar terlihat di foto.</p>
                    </div>
                    {{-- Custom size --}}
                    <div x-show="refObject === 'custom'" x-transition class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Lebar benda (cm)</label>
                            <input type="number" step="0.1" x-model="refWidthCm" @input="$dispatch('ref-custom-changed', {w: refWidthCm, h: refHeightCm})"
                                class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="cth: 21">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Tinggi benda (cm)</label>
                            <input type="number" step="0.1" x-model="refHeightCm" @input="$dispatch('ref-custom-changed', {w: refWidthCm, h: refHeightCm})"
                                class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="cth: 29.7">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Foto Badan <span class="text-red-500">*</span></label>
                        <input type="file" id="photoInput" accept="image/*" @change="onPhotoSelect($event)"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                        <p class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP. Maks 5MB.</p>
                        <div x-show="photoPreview" class="mt-3">
                            <img :src="photoPreview" id="previewImg" crossorigin="anonymous"
                                 class="max-h-56 rounded-xl border border-gray-200 shadow-sm object-contain" alt="Preview">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Tinggi Badan <span class="text-red-500">*</span>
                            <span class="text-xs text-gray-400 font-normal ml-1">(sebagai referensi skala)</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number" x-model="userHeight" min="100" max="250" step="1"
                                class="w-32 rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="cth: 165">
                            <span class="text-sm text-gray-500 font-medium">cm</span>
                        </div>
                    </div>
                    </div>{{-- end foto div --}}
                    <div x-show="errorMsg" x-cloak class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm" x-text="errorMsg"></div>
                    <button @click="analyze()" :disabled="!photoFile || !userHeight || isAnalyzing"
                        class="w-full px-6 py-3 bg-blue-600 text-white rounded-xl font-bold text-sm hover:bg-blue-700 transition-colors shadow-sm flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="!isAnalyzing"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></template>
                        <template x-if="isAnalyzing"><svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></template>
                        <span x-text="isAnalyzing ? 'Menganalisis... (pertama kali agak lama)' : 'Analisis Ukuran Sekarang'"></span>
                    </button>
                </div>
            </div>

            {{-- Hasil Analisis --}}
            <div x-show="showResults" x-transition x-cloak class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-50 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-gray-900">Hasil Analisis AI</h2>
                        <p class="text-sm text-gray-500 mt-0.5">Periksa dan edit jika perlu, lalu simpan.</p>
                    </div>
                    <div x-show="sizeLabel" class="text-center bg-blue-50 rounded-2xl px-5 py-2 border border-blue-100">
                        <div class="text-4xl font-black text-blue-600" x-text="sizeLabel"></div>
                        <p class="text-xs text-gray-500 mt-0.5 font-medium">Ukuran Baju</p>
                    </div>
                </div>
                <div class="px-6 py-3 border-b" :class="confidence >= 0.7 ? 'bg-green-50 border-green-100' : 'bg-amber-50 border-amber-100'">
                    <div class="flex justify-between mb-1">
                        <span class="text-xs font-semibold" :class="confidence >= 0.7 ? 'text-green-700' : 'text-amber-700'">Tingkat Keyakinan AI</span>
                        <span class="text-xs font-bold" :class="confidence >= 0.7 ? 'text-green-700' : 'text-amber-700'" x-text="Math.round(confidence * 100) + '%'"></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5 mb-1.5">
                        <div class="h-1.5 rounded-full" :class="confidence >= 0.7 ? 'bg-green-500' : 'bg-amber-400'" :style="'width:' + Math.round(confidence * 100) + '%'"></div>
                    </div>
                    <p class="text-xs" :class="confidence >= 0.7 ? 'text-green-600' : 'text-amber-600'">
                        <span x-show="confidence >= 0.7">Pose terdeteksi dengan baik. Nilai cukup akurat.</span>
                        <span x-show="confidence < 0.7">Beberapa titik kurang terlihat. Periksa dan edit jika perlu.</span>
                    </p>
                </div>
                <div class="px-6 py-3 bg-gray-50 border-b border-gray-100">
                    <p class="text-xs font-semibold text-gray-500 mb-2">Panduan Ukuran (lingkar dada)</p>
                    <div class="flex flex-wrap gap-2">
                        <div class="bg-white rounded-xl px-3 py-1.5 text-center border border-gray-200"><div class="text-sm font-black text-blue-600">S</div><div class="text-[10px] text-gray-400">&le;88cm</div></div>
                        <div class="bg-white rounded-xl px-3 py-1.5 text-center border border-gray-200"><div class="text-sm font-black text-blue-600">M</div><div class="text-[10px] text-gray-400">89-96cm</div></div>
                        <div class="bg-white rounded-xl px-3 py-1.5 text-center border border-gray-200"><div class="text-sm font-black text-blue-600">L</div><div class="text-[10px] text-gray-400">97-104cm</div></div>
                        <div class="bg-white rounded-xl px-3 py-1.5 text-center border border-gray-200"><div class="text-sm font-black text-blue-600">XL</div><div class="text-[10px] text-gray-400">105-112cm</div></div>
                        <div class="bg-white rounded-xl px-3 py-1.5 text-center border border-gray-200"><div class="text-sm font-black text-blue-600">XXL</div><div class="text-[10px] text-gray-400">113-120cm</div></div>
                        <div class="bg-white rounded-xl px-3 py-1.5 text-center border border-gray-200"><div class="text-sm font-black text-blue-600">XXXL</div><div class="text-[10px] text-gray-400">&gt;120cm</div></div>
                    </div>
                </div>
                <form action="{{ route('user.measurement.store') }}" method="POST" class="p-6">
                    @csrf
                    <input type="hidden" name="photo_path" value="">
                    <input type="hidden" name="ref_object" x-bind:value="refObject">
                    <input type="hidden" name="ref_size" x-bind:value="refObject === 'custom' ? (refWidthCm + 'x' + refHeightCm + 'cm') : ''">
                    <input type="hidden" name="is_edited" x-bind:value="isEdited ? 1 : 0">
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-6">
                        <div class="bg-gray-50 rounded-xl p-3"><label class="block text-xs font-semibold text-gray-500 mb-1.5">Lingkar Dada</label><div class="flex items-center gap-1"><input type="number" step="0.1" name="chest" x-model="chest" @input="isEdited=true; updateSize()" class="flex-1 bg-white rounded-lg border-gray-200 text-base font-bold focus:border-blue-500 focus:ring-blue-500 px-2 py-1.5 min-w-0"><span class="text-xs text-gray-400">cm</span></div></div>
                        <div class="bg-gray-50 rounded-xl p-3"><label class="block text-xs font-semibold text-gray-500 mb-1.5">Lingkar Pinggang</label><div class="flex items-center gap-1"><input type="number" step="0.1" name="waist" x-model="waist" @input="isEdited=true" class="flex-1 bg-white rounded-lg border-gray-200 text-base font-bold focus:border-blue-500 focus:ring-blue-500 px-2 py-1.5 min-w-0"><span class="text-xs text-gray-400">cm</span></div></div>
                        <div class="bg-gray-50 rounded-xl p-3"><label class="block text-xs font-semibold text-gray-500 mb-1.5">Lingkar Pinggul</label><div class="flex items-center gap-1"><input type="number" step="0.1" name="hips" x-model="hips" @input="isEdited=true" class="flex-1 bg-white rounded-lg border-gray-200 text-base font-bold focus:border-blue-500 focus:ring-blue-500 px-2 py-1.5 min-w-0"><span class="text-xs text-gray-400">cm</span></div></div>
                        <div class="bg-gray-50 rounded-xl p-3"><label class="block text-xs font-semibold text-gray-500 mb-1.5">Lebar Bahu</label><div class="flex items-center gap-1"><input type="number" step="0.1" name="shoulder_width" x-model="shoulderWidth" @input="isEdited=true" class="flex-1 bg-white rounded-lg border-gray-200 text-base font-bold focus:border-blue-500 focus:ring-blue-500 px-2 py-1.5 min-w-0"><span class="text-xs text-gray-400">cm</span></div></div>
                        <div class="bg-gray-50 rounded-xl p-3"><label class="block text-xs font-semibold text-gray-500 mb-1.5">Panjang Lengan</label><div class="flex items-center gap-1"><input type="number" step="0.1" name="arm_length" x-model="armLength" @input="isEdited=true" class="flex-1 bg-white rounded-lg border-gray-200 text-base font-bold focus:border-blue-500 focus:ring-blue-500 px-2 py-1.5 min-w-0"><span class="text-xs text-gray-400">cm</span></div></div>
                        <div class="bg-gray-50 rounded-xl p-3"><label class="block text-xs font-semibold text-gray-500 mb-1.5">Tinggi Badan</label><div class="flex items-center gap-1"><input type="number" step="0.1" name="height" x-model="height" @input="isEdited=true" class="flex-1 bg-white rounded-lg border-gray-200 text-base font-bold focus:border-blue-500 focus:ring-blue-500 px-2 py-1.5 min-w-0"><span class="text-xs text-gray-400">cm</span></div></div>
                    </div>
                    <div class="flex items-center justify-between pt-4 border-t border-gray-50">
                        <p class="text-xs text-amber-600 font-medium" x-show="isEdited" x-cloak>&#9998; Nilai telah diedit</p>
                        <div class="flex gap-3 ml-auto">
                            <button type="button" @click="showResults=false; resetForm()" class="px-4 py-2.5 text-sm text-gray-500 hover:text-gray-700 font-medium">Ulangi</button>
                            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl font-semibold text-sm hover:bg-blue-700 transition-colors shadow-sm">Simpan Ukuran Ini</button>
                        </div>
                    </div>
                </form>
            </div>

        </div>

        {{-- Kolom Kanan: Riwayat --}}
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
                        @php
                            $cv = floatval($m->chest);
                            if ($cv <= 88) $sz = 'S';
                            elseif ($cv <= 96) $sz = 'M';
                            elseif ($cv <= 104) $sz = 'L';
                            elseif ($cv <= 112) $sz = 'XL';
                            elseif ($cv <= 120) $sz = 'XXL';
                            else $sz = 'XXXL';
                        @endphp
                        <div class="border border-gray-100 rounded-2xl p-4 hover:border-blue-100 transition-colors">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <p class="text-xs font-semibold text-gray-500">{{ $m->created_at->format('d M Y, H:i') }}</p>
                                    @if($m->is_edited)<span class="text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full font-medium">Diedit</span>@endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xl font-black text-blue-600">{{ $sz }}</span>
                                    <form action="{{ route('user.measurement.destroy', $m) }}" method="POST" onsubmit="return confirm('Hapus data ukuran ini?')">
                                        @csrf @method('DELETE')
                                        <button class="text-gray-300 hover:text-red-500 transition-colors p-0.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/@mediapipe/pose@0.5.1675469404/pose.js" crossorigin="anonymous"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('measureApp', () => ({
        photoFile: null, photoPreview: null, userHeight: '',
        refObject: 'a4', refWidthCm: '', refHeightCm: '',
        isAnalyzing: false, showResults: false, errorMsg: '',
        confidence: 0, isEdited: false, sizeLabel: '',
        chest: 0, waist: 0, hips: 0, shoulderWidth: 0, armLength: 0, height: 0,

        init() {
            this.$el.addEventListener('ref-changed', (e) => { this.refObject = e.detail; });
            this.$el.addEventListener('ref-custom-changed', (e) => {
                this.refWidthCm = e.detail.w; this.refHeightCm = e.detail.h;
            });
        },

        onPhotoSelect(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.photoFile = file;
            this.photoPreview = URL.createObjectURL(file);
            this.showResults = false; this.errorMsg = ''; this.isEdited = false;
        },

        resetForm() {
            this.photoFile = null; this.photoPreview = null;
            this.errorMsg = ''; this.isEdited = false; this.sizeLabel = '';
            const inp = document.getElementById('photoInput');
            if (inp) inp.value = '';
        },

        getSizeFromChest(c) {
            if (c <= 88) return 'S';
            if (c <= 96) return 'M';
            if (c <= 104) return 'L';
            if (c <= 112) return 'XL';
            if (c <= 120) return 'XXL';
            return 'XXXL';
        },

        updateSize() {
            const c = parseFloat(this.chest) || 0;
            this.sizeLabel = c > 0 ? this.getSizeFromChest(c) : '';
        },

        async analyze() {
            if (!this.photoFile || !this.userHeight || this.isAnalyzing) return;
            this.isAnalyzing = true; this.errorMsg = ''; this.showResults = false;
            try { await this.runPose(); }
            catch(e) { this.errorMsg = e.message || 'Terjadi kesalahan saat menganalisis foto.'; }
            finally { this.isAnalyzing = false; }
        },

        runPose() {
            return new Promise((resolve, reject) => {
                if (typeof Pose === 'undefined') {
                    return reject(new Error('Library AI belum siap. Pastikan koneksi internet aktif, lalu muat ulang halaman.'));
                }
                const imgEl = document.getElementById('previewImg');
                if (!imgEl || !imgEl.complete || imgEl.naturalWidth === 0) {
                    return reject(new Error('Gambar belum siap. Tunggu sebentar lalu coba lagi.'));
                }
                const userH = parseFloat(this.userHeight);
                if (!userH || userH < 100 || userH > 250) {
                    return reject(new Error('Tinggi badan harus antara 100-250 cm.'));
                }
                if (this.refObject === 'custom' && (!parseFloat(this.refWidthCm) || !parseFloat(this.refHeightCm))) {
                    return reject(new Error('Masukkan ukuran lebar dan tinggi benda referensi custom.'));
                }

                let done = false;
                const pose = new Pose({
                    locateFile: (f) => `https://cdn.jsdelivr.net/npm/@mediapipe/pose@0.5.1675469404/${f}`
                });
                pose.setOptions({
                    modelComplexity: 1, smoothLandmarks: false,
                    enableSegmentation: false,
                    minDetectionConfidence: 0.5, minTrackingConfidence: 0.5,
                });

                pose.onResults((results) => {
                    if (done) return;
                    done = true;
                    try {
                        const lm = results.poseLandmarks;
                        if (!lm || lm.length < 29) {
                            return reject(new Error('Pose tidak terdeteksi. Pastikan seluruh badan terlihat jelas dari kepala hingga kaki, dengan pencahayaan yang cukup.'));
                        }
                        const W = imgEl.naturalWidth, H = imgEl.naturalHeight;
                        const pt = (i) => ({ x: lm[i].x * W, y: lm[i].y * H });
                        const dist = (a, b) => Math.sqrt((a.x-b.x)**2 + (a.y-b.y)**2);
                        const r1 = (v) => Math.round(v * 10) / 10;

                        const nose=pt(0), lS=pt(11), rS=pt(12),
                              lE=pt(13), rE=pt(14), lW=pt(15), rW=pt(16),
                              lH=pt(23), rH=pt(24), lA=pt(27), rA=pt(28);

                        const ankleY = (lA.y + rA.y) / 2;
                        const bodyHPx = (ankleY - nose.y) * 1.1;
                        if (bodyHPx <= 0) {
                            return reject(new Error('Tidak bisa menghitung skala. Pastikan kepala dan kaki keduanya terlihat dalam foto.'));
                        }

                        const scale = bodyHPx / userH;
                        const sW = dist(lS, rS);
                        const hW = dist(lH, rH);
                        const lArm = dist(lS,lE) + dist(lE,lW);
                        const rArm = dist(rS,rE) + dist(rE,rW);

                        this.chest         = r1((sW * 1.6) / scale);
                        this.waist         = r1((hW * 1.2) / scale);
                        this.hips          = r1((hW * 1.5) / scale);
                        this.shoulderWidth = r1(sW / scale);
                        this.armLength     = r1(((lArm + rArm) / 2) / scale);
                        this.height        = userH;

                        this.confidence = lm.filter(l => l.visibility > 0.5).length / 33;
                        this.updateSize();
                        this.showResults = true;
                        this.isEdited = false;
                        resolve();
                    } catch(e) { reject(e); }
                });

                pose.send({ image: imgEl }).catch((err) => {
                    if (!done) {
                        done = true;
                        reject(new Error('Gagal memproses gambar: ' + (err.message || err)));
                    }
                });
            });
        },
    }));
});
</script>
@endpush

@endsection
