@extends('layouts.user')
@section('page-title', 'Ukur Badan')
@section('content')

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8" x-data="measurementCapture()" x-init="init()">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Ukur Badan Multi-view</h1>
        <p class="text-gray-500 text-sm mt-1">Ambil foto langsung dari kamera atau upload foto depan, samping, dan belakang dengan benda patokan ukuran A4 atau KTP.</p>
    </div>

    @if(session('photo_issues') && count(session('photo_issues')) > 0)
    <div class="mb-6 bg-white border border-red-200 rounded-xl shadow-sm overflow-hidden">
        <div class="flex items-start gap-4 p-5 bg-red-50 border-b border-red-100">
            <div class="w-11 h-11 rounded-xl bg-red-100 border border-red-200 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M4.93 19h14.14c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.2 16c-.77 1.33.19 3 1.73 3z"/></svg>
            </div>
            <div>
                <p class="font-bold text-red-900">Foto belum bisa dianalisis</p>
                <p class="text-sm text-red-700 mt-1">Perbaiki bagian yang ditandai di bawah, lalu ambil ulang foto.</p>
            </div>
        </div>
        <div class="p-5">
            <ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach(session('photo_issues') as $issue)
                <li class="flex items-start gap-3 rounded-xl border border-red-100 bg-red-50/60 p-3 text-sm text-red-800">
                    <span class="mt-0.5 h-5 w-5 rounded-full bg-red-100 text-red-700 flex items-center justify-center text-xs font-black">!</span>
                    <span>{{ $issue }}</span>
                </li>
                @endforeach
            </ul>
        </div>
        @if(session('photo_suggestion'))
        <p class="mx-5 mb-5 text-sm text-amber-800 bg-amber-50 border border-amber-100 rounded-lg p-3">{{ session('photo_suggestion') }}</p>
        @endif
    </div>
    @elseif(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 text-red-700 text-sm">{{ session('error') }}</div>
    @endif

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4 text-green-700 text-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-5">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-center justify-between gap-4 mb-5">
                    <div>
                        <h2 class="font-bold text-gray-900">Protokol Pengambilan Foto</h2>
                        <p class="text-sm text-gray-500 mt-1">Mode akurat memakai A4/KTP yang ditempel atau disandarkan. Jika perlu lebih praktis, A4 boleh dipegang di samping tubuh dengan confidence lebih rendah.</p>
                    </div>
                    <span class="text-xs font-semibold px-3 py-1.5 rounded-full bg-blue-50 text-blue-700">3 foto wajib</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @foreach([
                        ['Depan', 'Badan menghadap kamera. A4/KTP tegak di samping tubuh dan tidak menutup siluet.'],
                        ['Samping', 'User menghadap kiri/kanan. A4/KTP tetap terlihat kamera, berdiri sejajar jarak tubuh.'],
                        ['Belakang', 'Punggung menghadap kamera. A4/KTP tetap di samping tubuh dan terlihat penuh.'],
                    ] as [$title, $desc])
                    <div class="border border-gray-100 rounded-xl p-4">
                        <p class="font-semibold text-gray-900 text-sm">{{ $title }}</p>
                        <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $desc }}</p>
                    </div>
                    @endforeach
                </div>

                <div class="mt-4 bg-amber-50 border border-amber-100 rounded-xl p-4">
                    <p class="text-sm font-semibold text-amber-900">Aturan wajib</p>
                    <p class="text-xs text-amber-800 mt-1 leading-relaxed">Kamera sejajar dada/pinggang, tubuh penuh kepala sampai kaki, pakaian fit, tangan rileks sedikit menjauh dari badan, pencahayaan cukup, dan benda patokan berada pada bidang yang sama dengan tubuh. A4 otomatis dihitung 21,0×29,7 cm; KTP otomatis dihitung 8,56×5,398 cm.</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <div class="flex flex-col gap-1 mb-5">
                    <h2 class="font-bold text-gray-900">Kamera Langsung</h2>
                    <p class="text-sm text-gray-500">Pilih pose, ikuti sketsa di frame kamera, lalu capture. Foto akan otomatis masuk ke form analisis.</p>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-[1fr_260px] gap-5">
                    <div class="space-y-3">
                        <div class="flex flex-wrap gap-2">
                            <template x-for="pose in poseList" :key="pose.key">
                                <button type="button" @click="setPose(pose.key)"
                                    class="px-3 py-2 rounded-lg text-xs font-bold border transition-colors"
                                    :class="activePose === pose.key ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'">
                                    <span x-text="pose.label"></span>
                                </button>
                            </template>
                        </div>

                        <div class="grid grid-cols-2 gap-2 rounded-xl border border-gray-100 bg-gray-50 p-2">
                            <button type="button" @click="setCameraFacing('environment')"
                                class="rounded-lg px-3 py-2 text-xs font-bold transition-colors"
                                :class="cameraFacing === 'environment' ? 'bg-slate-900 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'">
                                Kamera belakang
                            </button>
                            <button type="button" @click="setCameraFacing('user')"
                                class="rounded-lg px-3 py-2 text-xs font-bold transition-colors"
                                :class="cameraFacing === 'user' ? 'bg-slate-900 text-white' : 'bg-white text-gray-600 hover:bg-gray-100'">
                                Kamera depan
                            </button>
                        </div>

                        <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-slate-950 aspect-[4/3]">
                            <video x-ref="video" autoplay playsinline muted class="absolute inset-0 w-full h-full object-cover"></video>

                            <div class="absolute inset-0 pointer-events-none">
                                <div class="absolute inset-x-0 top-0 h-20 bg-gradient-to-b from-black/45 to-transparent"></div>
                                <div class="absolute left-3 top-3 rounded-lg bg-black/55 px-3 py-1.5 text-xs font-semibold text-white" x-text="activePoseLabel"></div>
                            </div>

                            <svg class="absolute inset-0 h-full w-full pointer-events-none" viewBox="0 0 400 300" preserveAspectRatio="none">
                                <rect x="282" y="50" width="54" height="144" rx="4" fill="rgba(14,165,233,.12)" stroke="#38bdf8" stroke-width="3"/>
                                <path d="M291 68 H327 M291 86 H327 M291 104 H327 M291 122 H327 M291 140 H327 M291 158 H327 M291 176 H327 M300 58 V186 M318 58 V186" stroke="#7dd3fc" stroke-width="1.4" opacity=".9"/>
                                <text x="309" y="205" text-anchor="middle" fill="#e0f2fe" font-size="10" font-weight="700">A4 / KTP</text>
                                <g x-show="activePose === 'front'">
                                        <ellipse cx="184" cy="55" rx="17" ry="20" fill="rgba(248,250,252,.18)" stroke="#f8fafc" stroke-width="3"/>
                                        <path d="M151 91 Q184 78 217 91 L207 168 Q184 181 161 168 Z" fill="rgba(248,250,252,.14)" stroke="#f8fafc" stroke-width="3"/>
                                        <path d="M154 103 L124 166" stroke="#f8fafc" stroke-width="10" stroke-linecap="round" opacity=".85"/>
                                        <path d="M214 103 L244 166" stroke="#f8fafc" stroke-width="10" stroke-linecap="round" opacity=".85"/>
                                        <path d="M166 171 L145 258" stroke="#f8fafc" stroke-width="12" stroke-linecap="round" opacity=".85"/>
                                        <path d="M202 171 L223 258" stroke="#f8fafc" stroke-width="12" stroke-linecap="round" opacity=".85"/>
                                        <path d="M158 143 Q184 153 210 143" fill="none" stroke="#22c55e" stroke-width="4" stroke-linecap="round"/>
                                </g>
                                <g x-show="activePose === 'side'">
                                        <ellipse cx="184" cy="55" rx="14" ry="20" fill="rgba(248,250,252,.18)" stroke="#f8fafc" stroke-width="3"/>
                                        <path d="M175 83 Q207 95 199 170 Q185 181 172 170 Q159 116 175 83 Z" fill="rgba(248,250,252,.14)" stroke="#f8fafc" stroke-width="3"/>
                                        <path d="M174 105 L148 164" stroke="#f8fafc" stroke-width="10" stroke-linecap="round" opacity=".85"/>
                                        <path d="M194 105 L220 164" stroke="#f8fafc" stroke-width="10" stroke-linecap="round" opacity=".85"/>
                                        <path d="M176 171 L160 258" stroke="#f8fafc" stroke-width="12" stroke-linecap="round" opacity=".85"/>
                                        <path d="M195 171 L212 258" stroke="#f8fafc" stroke-width="12" stroke-linecap="round" opacity=".85"/>
                                        <path d="M158 136 Q184 149 211 136" fill="none" stroke="#22c55e" stroke-width="4" stroke-linecap="round"/>
                                </g>
                                <g x-show="activePose === 'back'">
                                        <ellipse cx="184" cy="55" rx="17" ry="20" fill="rgba(248,250,252,.18)" stroke="#f8fafc" stroke-width="3"/>
                                        <path d="M150 92 Q184 80 218 92 L208 169 Q184 181 160 169 Z" fill="rgba(248,250,252,.14)" stroke="#f8fafc" stroke-width="3"/>
                                        <path d="M153 104 L124 166" stroke="#f8fafc" stroke-width="10" stroke-linecap="round" opacity=".85"/>
                                        <path d="M215 104 L244 166" stroke="#f8fafc" stroke-width="10" stroke-linecap="round" opacity=".85"/>
                                        <path d="M166 171 L145 258" stroke="#f8fafc" stroke-width="12" stroke-linecap="round" opacity=".85"/>
                                        <path d="M202 171 L223 258" stroke="#f8fafc" stroke-width="12" stroke-linecap="round" opacity=".85"/>
                                        <path d="M151 94 L151 170 L217 170 L217 94" fill="none" stroke="#22c55e" stroke-width="3" stroke-dasharray="7 5"/>
                                </g>
                            </svg>

                            <div x-show="!cameraReady" class="absolute inset-0 flex items-center justify-center bg-slate-950/85 p-6 text-center">
                                <div>
                                    <p class="text-sm font-semibold text-white" x-text="cameraError || 'Kamera belum aktif'"></p>
                                    <p class="text-xs text-slate-300 mt-1">Izinkan akses kamera dari browser untuk memakai mode capture.</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-100 bg-white p-4">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <p class="text-sm font-bold text-gray-900">Status kamera</p>
                                <span class="text-[11px] font-bold px-2 py-1 rounded-full" :class="liveReport.captureReady ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'" x-text="liveReport.captureReady ? 'Siap ambil foto' : 'Ikuti panduan frame'"></span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <template x-for="item in liveReport.checks" :key="item.label">
                                    <div class="flex items-start gap-2 rounded-lg border px-3 py-2 text-xs" :class="item.ok ? 'border-green-100 bg-green-50 text-green-800' : 'border-amber-100 bg-amber-50 text-amber-800'">
                                        <span class="mt-0.5 h-4 w-4 rounded-full flex items-center justify-center text-[10px] font-black" :class="item.ok ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'" x-text="item.ok ? 'OK' : '!'"></span>
                                        <span x-text="item.label"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <button type="button" @click="startCamera()" class="px-4 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-bold hover:bg-slate-800">
                                Aktifkan Kamera
                            </button>
                            <button type="button" @click="capturePose(activePose)" :disabled="!cameraReady || !liveReport.captureReady"
                                class="px-4 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-bold hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed">
                                Capture <span x-text="activePoseLabel"></span>
                            </button>
                            <button type="button" @click="stopCamera()" class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 text-sm font-semibold hover:bg-gray-50">
                                Matikan
                            </button>
                        </div>
                        <canvas x-ref="canvas" class="hidden"></canvas>
                    </div>

                    <div class="grid grid-cols-3 xl:grid-cols-1 gap-3">
                        <template x-for="pose in poseList" :key="pose.key">
                            <div class="rounded-xl border border-gray-100 bg-gray-50 p-3">
                                <p class="text-xs font-bold text-gray-700 mb-2" x-text="pose.label"></p>
                                <div class="h-24 rounded-lg bg-white border border-gray-100 overflow-hidden">
                                    <img x-show="previews[pose.key]" :src="previews[pose.key]" class="h-full w-full object-contain" :alt="`Preview ${pose.label}`">
                                    <div x-show="!previews[pose.key]" class="h-full flex items-center justify-center text-[11px] text-gray-400">Belum ada foto</div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h2 class="font-bold text-gray-900 mb-5">Contoh Visual Untuk Upload Foto</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <template x-for="pose in poseList" :key="`example-${pose.key}`">
                        <div class="rounded-xl border border-gray-100 p-4">
                            <p class="text-sm font-bold text-gray-900" x-text="pose.label"></p>
                            <div class="mt-3 h-56 rounded-xl bg-gradient-to-b from-sky-50 via-white to-slate-100 border border-gray-100 overflow-hidden">
                                <svg viewBox="0 0 260 230" class="h-full w-full">
                                    <rect x="0" y="198" width="260" height="32" fill="#e2e8f0"/>
                                    <rect x="182" y="42" width="42" height="118" rx="4" fill="#e0f2fe" stroke="#0284c7" stroke-width="3"/>
                                    <path d="M190 60 H216 M190 78 H216 M190 96 H216 M190 114 H216 M190 132 H216 M190 150 H216 M196 50 V154 M210 50 V154" stroke="#38bdf8" stroke-width="1.2"/>
                                    <text x="203" y="176" text-anchor="middle" fill="#0369a1" font-size="9" font-weight="700">A4/KTP</text>

                                    <g x-show="pose.key === 'front'">
                                        <circle cx="92" cy="47" r="19" fill="#334155"/>
                                        <path d="M58 82 Q92 66 126 82 L116 151 Q92 163 68 151 Z" fill="#475569"/>
                                        <path d="M61 91 L31 149" stroke="#475569" stroke-width="13" stroke-linecap="round"/>
                                        <path d="M123 91 L153 149" stroke="#475569" stroke-width="13" stroke-linecap="round"/>
                                        <path d="M74 153 L57 205" stroke="#334155" stroke-width="14" stroke-linecap="round"/>
                                        <path d="M110 153 L127 205" stroke="#334155" stroke-width="14" stroke-linecap="round"/>
                                        <path d="M66 128 Q92 138 118 128" fill="none" stroke="#16a34a" stroke-width="4" stroke-linecap="round"/>
                                    </g>

                                    <g x-show="pose.key === 'side'">
                                        <ellipse cx="92" cy="47" rx="15" ry="19" fill="#334155"/>
                                        <path d="M83 75 Q116 88 108 151 Q93 162 80 151 Q67 105 83 75 Z" fill="#475569"/>
                                        <path d="M81 94 L54 148" stroke="#475569" stroke-width="13" stroke-linecap="round"/>
                                        <path d="M104 94 L131 148" stroke="#475569" stroke-width="13" stroke-linecap="round"/>
                                        <path d="M84 153 L68 205" stroke="#334155" stroke-width="14" stroke-linecap="round"/>
                                        <path d="M105 153 L121 205" stroke="#334155" stroke-width="14" stroke-linecap="round"/>
                                        <path d="M65 124 Q93 137 121 124" fill="none" stroke="#16a34a" stroke-width="4" stroke-linecap="round"/>
                                    </g>

                                    <g x-show="pose.key === 'back'">
                                        <circle cx="92" cy="47" r="19" fill="#334155"/>
                                        <path d="M58 82 Q92 66 126 82 L116 151 Q92 163 68 151 Z" fill="#475569"/>
                                        <path d="M61 91 L31 149" stroke="#475569" stroke-width="13" stroke-linecap="round"/>
                                        <path d="M123 91 L153 149" stroke="#475569" stroke-width="13" stroke-linecap="round"/>
                                        <path d="M74 153 L57 205" stroke="#334155" stroke-width="14" stroke-linecap="round"/>
                                        <path d="M110 153 L127 205" stroke="#334155" stroke-width="14" stroke-linecap="round"/>
                                        <path d="M60 83 L60 151 L124 151 L124 83" fill="none" stroke="#16a34a" stroke-width="3" stroke-dasharray="6 5"/>
                                    </g>
                                </svg>
                            </div>
                            <p class="text-xs text-gray-500 mt-3" x-text="pose.hint"></p>
                        </div>
                    </template>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h2 class="font-bold text-gray-900 mb-5">Analisis Ukuran dengan Computer Vision</h2>

                <form x-ref="analysisForm" action="{{ route('user.measurement.analysis-start') }}" method="POST" enctype="multipart/form-data" class="space-y-5" novalidate @submit.prevent="startAnalysis($event)">
                    @csrf

                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-3 sm:hidden">
                        <button type="submit" :disabled="isAnalyzing"
                            class="flex min-h-12 w-full touch-manipulation items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-black text-white shadow-sm focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-wait disabled:opacity-70">
                            <span x-show="!isAnalyzing">Validasi dan Hitung Ukuran</span>
                            <span x-show="isAnalyzing" x-cloak>Analisis sedang berjalan...</span>
                        </button>
                        <p class="mt-1.5 text-center text-[11px] text-blue-700">Bisa ditekan setelah tiga foto dipilih dan kotak merah diperiksa.</p>
                    </div>
                    <div x-show="totalUploadError" x-cloak
                        class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold leading-relaxed text-red-700 sm:hidden"
                        role="alert" aria-live="assertive" x-text="totalUploadError"></div>

                    <div>
                        <label for="ref_object" class="block text-sm font-semibold text-gray-700 mb-1.5">Benda Patokan Ukuran <span class="text-red-500">*</span></label>
                        <select name="ref_object" id="ref_object" x-model="refObject" @change="syncReferenceMode()" class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="a4">Kertas A4 polos - 21,0 x 29,7 cm</option>
                            <option value="ktp">KTP - 8,56 x 5,398 cm</option>
                        </select>
                    </div>

                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <div class="flex items-center justify-between gap-3 mb-3">
                            <div>
                                <p class="text-sm font-bold text-gray-900">Mode Patokan</p>
                                <p class="text-xs text-gray-500 mt-0.5">Pilih cara benda patokan diletakkan saat foto diambil.</p>
                            </div>
                            <span x-show="refObject === 'ktp'" class="text-[11px] font-bold text-amber-700 bg-amber-100 px-2 py-1 rounded-full">KTP wajib ditempel</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="cursor-pointer rounded-xl border p-4 transition-colors" :class="referenceMode === 'fixed' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white hover:bg-gray-50'">
                                <input type="radio" name="reference_mode" value="fixed" x-model="referenceMode" class="sr-only">
                                <span class="block text-sm font-black text-gray-900">Mode Akurat</span>
                                <span class="block text-xs text-gray-500 mt-1 leading-relaxed">A4/KTP ditempel atau disandarkan. Direkomendasikan untuk hasil lebih stabil.</span>
                            </label>
                            <label class="rounded-xl border p-4 transition-colors" :class="refObject === 'ktp' ? 'border-gray-100 bg-gray-100 opacity-60 cursor-not-allowed' : (referenceMode === 'handheld' ? 'border-amber-500 bg-amber-50 cursor-pointer' : 'border-gray-200 bg-white hover:bg-gray-50 cursor-pointer')">
                                <input type="radio" name="reference_mode" value="handheld" x-model="referenceMode" :disabled="refObject === 'ktp'" class="sr-only">
                                <span class="block text-sm font-black text-gray-900">Mode Praktis</span>
                                <span class="block text-xs text-gray-500 mt-1 leading-relaxed">A4 boleh dipegang di samping tubuh. Confidence akan diturunkan.</span>
                            </label>
                        </div>
                        @error('reference_mode')
                        <p class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
                        <p class="text-sm font-bold text-blue-900">Ukuran benda patokan otomatis</p>
                        <p class="text-xs text-blue-800 mt-1 leading-relaxed" x-show="refObject === 'a4'">
                            Sistem memakai ukuran A4 tetap: 21,0 cm x 29,7 cm. Tidak perlu mengisi ukuran manual.
                        </p>
                        <p class="text-xs text-blue-800 mt-1 leading-relaxed" x-show="refObject === 'ktp'">
                            Sistem memakai ukuran KTP tetap: 8,56 cm x 5,398 cm. Tempelkan KTP pada papan/dinding, jangan dipegang oleh user.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        @foreach([
                            ['front_photo', 'front', 'Foto Depan', 'User menghadap kamera'],
                            ['side_photo', 'side', 'Foto Samping', 'User menghadap kiri/kanan'],
                            ['back_photo', 'back', 'Foto Belakang', 'Punggung menghadap kamera'],
                        ] as [$name, $key, $label, $hint])
                        <div data-upload-card="{{ $key }}" class="border rounded-xl p-4 transition-all" :class="uploadErrors.{{ $key }} ? 'border-red-400 bg-red-50/60 ring-2 ring-red-100 shadow-sm' : 'border-gray-100 bg-white'">
                            <div class="mb-1.5 flex items-center justify-between gap-3">
                                <label for="{{ $name }}" class="block text-sm font-semibold text-gray-700">{{ $label }} <span class="text-red-500">*</span></label>
                                <span x-show="uploadErrors.{{ $key }}" x-cloak class="rounded-full bg-red-600 px-2 py-1 text-[10px] font-black text-white">PERLU DIPERBAIKI</span>
                            </div>
                            <p class="text-xs text-gray-400 mb-3">{{ $hint }}</p>
                            <input type="file" name="{{ $name }}" id="{{ $name }}" x-ref="{{ $key }}Input" accept="image/*" required
                                   @change="handleUpload($event, '{{ $key }}')"
                                   class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                            <input type="hidden" name="{{ $key }}_reference_box" :value="manualReferenceBoxes.{{ $key }} ? JSON.stringify(manualReferenceBoxes.{{ $key }}) : ''">
                            <div x-show="uploadErrors.{{ $key }}" x-cloak class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3" role="alert">
                                <p class="text-xs font-black text-red-800">Periksa {{ $label }}</p>
                                <p class="mt-1 text-xs font-semibold leading-relaxed text-red-700" x-text="uploadErrors.{{ $key }}"></p>
                                <button type="button" x-show="previews.{{ $key }}" @click="openReferenceEditor('{{ $key }}')"
                                    class="mt-2 min-h-10 touch-manipulation rounded-lg bg-red-600 px-3 py-2 text-xs font-black text-white">
                                    Perbesar dan koreksi kotak {{ $label }}
                                </button>
                            </div>
                            @error($name)
                            <div class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700">{{ $message }}</div>
                            @enderror
                            <div x-show="previews.{{ $key }}" class="mt-3">
                                <img :src="previews.{{ $key }}" class="h-48 w-full rounded-lg border border-gray-200 object-contain bg-gray-50" alt="Preview {{ $label }}">
                            </div>
                            <div x-show="detectionReports.{{ $key }}" x-cloak class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-slate-50 shadow-sm">
                                <div class="border-b border-slate-200 bg-white px-4 py-3">
                                 <div class="flex items-center justify-between gap-2 mb-2">
                                    <div>
                                        <p class="text-xs font-black text-slate-900">Pemeriksaan foto</p>
                                        <p class="mt-0.5 text-[11px] text-slate-500">Oranye mengikuti tubuh, merah mengikuti A4/KTP.</p>
                                    </div>
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full" :class="detectionReports.{{ $key }}?.ready ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'" x-text="detectionReports.{{ $key }}?.ready ? 'Siap dianalisis' : 'Perlu dicek'"></span>
                                 </div>
                                </div>
                                <div class="bg-slate-900 p-2">
                                <div class="overflow-hidden rounded-lg bg-slate-800">
                                    <canvas x-ref="{{ $key }}DetectionCanvas"
                                        class="block w-full cursor-move touch-none"
                                        @mousedown="startManualReference($event, '{{ $key }}')"
                                        @mousemove="updateManualReference($event, '{{ $key }}')"
                                        @mouseup="finishManualReference($event, '{{ $key }}')"
                                        @mouseleave="cancelManualReference('{{ $key }}')"
                                        @touchstart.prevent="startManualReference($event, '{{ $key }}')"
                                        @touchmove.prevent="updateManualReference($event, '{{ $key }}')"
                                        @touchend.prevent="finishManualReference($event, '{{ $key }}')"></canvas>
                                </div>
                                </div>
                                <div class="border-t border-slate-200 bg-white p-4">
                                    <p class="text-xs font-bold text-slate-900">Kotak merah benda patokan</p>
                                    <p class="mt-1 text-[11px] leading-relaxed text-slate-500">Kotak dibuat otomatis. Jika belum tepat, perbesar foto lalu geser atau tarik sudutnya sampai menempel pada empat tepi A4/KTP.</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <button type="button" @click="createManualReferenceBox('{{ $key }}')"
                                            class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-[11px] font-bold text-red-700">
                                            Deteksi ulang kotak
                                        </button>
                                        <button type="button" @click="openReferenceEditor('{{ $key }}')"
                                            class="rounded-lg bg-slate-900 px-3 py-1.5 text-[11px] font-bold text-white border border-slate-900">
                                            Perbesar dan koreksi
                                        </button>
                                        <button type="button" @click="clearManualReferenceBox('{{ $key }}')" x-show="manualReferenceBoxes.{{ $key }}" x-cloak
                                            class="rounded-lg bg-white px-3 py-1.5 text-[11px] font-bold text-red-600 border border-red-100">
                                            Hapus kotak manual
                                        </button>
                                    </div>
                                    <p x-show="manualReferenceBoxes.{{ $key }}" x-cloak class="mt-2 text-[11px] font-bold text-red-700">Kotak merah aktif dan akan dipakai sebagai skala {{ $label }}.</p>
                                </div>
                                <ul class="space-y-1.5 border-t border-slate-200 bg-white px-4 py-3">
                                    <template x-for="item in detectionReports.{{ $key }}?.checks || []" :key="item.label">
                                        <li class="flex items-start gap-2 text-xs">
                                            <span class="mt-0.5 h-4 w-4 rounded-full flex items-center justify-center text-[9px] font-black" :class="item.ok ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'" x-text="item.ok ? 'OK' : '!'"></span>
                                            <span class="text-gray-600" x-text="item.label"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div x-show="totalUploadError" x-cloak class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" x-text="totalUploadError"></div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-2 shadow-sm sm:border-0 sm:bg-transparent sm:p-0 sm:shadow-none"
                        style="padding-bottom: max(0.5rem, env(safe-area-inset-bottom));">
                        <button type="submit" :disabled="isAnalyzing"
                            class="flex min-h-12 w-full touch-manipulation items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-black text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:cursor-wait disabled:opacity-70">
                            <span x-show="!isAnalyzing">Validasi dan Hitung Ukuran</span>
                            <span x-show="isAnalyzing" x-cloak>Analisis sedang berjalan...</span>
                        </button>
                        <p class="mt-1.5 text-center text-[11px] text-slate-500 sm:hidden">Pastikan tiga foto dan kotak merah sudah benar.</p>
                    </div>
                </form>
            </div>
        </div>

        <div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
                <h2 class="font-bold text-gray-900 mb-5">Riwayat Pengukuran</h2>

                @if($measurements->isEmpty())
                    <div class="py-10 text-center">
                        <p class="text-gray-500 font-medium text-sm">Belum ada data ukuran</p>
                        <p class="text-xs text-gray-400 mt-0.5">Upload tiga foto untuk analisis CV.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($measurements as $m)
                        <div class="border border-gray-100 rounded-xl p-4">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <p class="text-xs font-semibold text-gray-500">{{ $m->created_at->format('d M Y, H:i') }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $m->measurement_method_label }} - {{ $m->ref_object_label }}</p>
                                    @if($m->bodym_contract_version)
                                    <p class="text-xs text-emerald-600 mt-0.5">{{ $m->bodym_contract_version }} @if($m->bodym_model_version) - {{ $m->bodym_model_version }} @endif</p>
                                    @endif
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $m->reference_mode_label }}</p>
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
                                @foreach([['Lingkar Dada',$m->chest],['Lingkar Pinggang',$m->waist],['Lingkar Pinggul',$m->hips],['Lebar Bahu',$m->shoulder_width],['Panjang Lengan',$m->arm_length],['Tinggi Badan',$m->height]] as [$lbl,$val])
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

    <div x-show="isAnalyzing" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/75 p-2 backdrop-blur-sm sm:p-4">
        <div class="flex max-h-[calc(100dvh-1rem)] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-white/70 bg-white shadow-2xl sm:max-h-[calc(100dvh-2rem)]">
            <div class="bg-slate-900 px-5 py-4 text-white sm:px-6 sm:py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold text-sky-200 sm:text-sm">Analisis ukuran sedang berjalan</p>
                        <h3 class="mt-1 text-lg font-black sm:text-xl">Mengecek foto dan menghitung ukuran tubuh</h3>
                    </div>
                    <span class="shrink-0 rounded-lg bg-white/10 px-2.5 py-1 text-xs font-black" x-text="`${analysisProgress.percent || 0}%`"></span>
                </div>
                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/15">
                    <div class="h-full rounded-full bg-sky-400 transition-all duration-500" :style="`width: ${analysisProgress.percent || 0}%`"></div>
                </div>
            </div>
            <div class="overflow-y-auto p-4 sm:p-6">
                <div class="mb-4 flex items-center gap-3 sm:mb-5 sm:gap-4">
                    <div class="relative h-12 w-12 flex-shrink-0 sm:h-16 sm:w-16">
                        <div class="absolute inset-0 rounded-full border-4 border-sky-100"></div>
                        <div class="absolute inset-0 rounded-full border-4 border-sky-500 border-t-transparent animate-spin"></div>
                        <div class="absolute inset-3 rounded-full bg-sky-50 sm:inset-4"></div>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-900" x-text="analysisProgress.message || 'Mengirim foto ke layanan analisis'"></p>
                        <p class="mt-1 text-xs text-gray-500">Status berubah setelah tahap CV benar-benar selesai.</p>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2 mb-4">
                    <template x-for="view in poseList" :key="view.key">
                        <div class="rounded-lg border px-2 py-2 text-center" :class="analysisViewState(view.key) === 'active' ? 'border-sky-200 bg-sky-50' : (analysisViewState(view.key) === 'done' ? 'border-green-200 bg-green-50' : 'border-slate-100 bg-slate-50')">
                            <p class="truncate text-[10px] font-black text-slate-700" x-text="view.label.replace('Foto ', '')"></p>
                            <p class="mt-0.5 text-[10px]" :class="analysisViewState(view.key) === 'active' ? 'text-sky-700' : (analysisViewState(view.key) === 'done' ? 'text-green-700' : 'text-slate-400')" x-text="analysisViewLabel(view.key)"></p>
                        </div>
                    </template>
                </div>
                <div class="space-y-2">
                    <template x-for="item in analysisTimeline" :key="item.stage">
                        <div class="flex items-start gap-3 rounded-xl border p-3" :class="item.current ? 'border-sky-200 bg-sky-50' : 'border-green-100 bg-green-50/60'">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-black" :class="item.current ? 'bg-sky-600 text-white' : 'bg-green-100 text-green-700'" x-text="item.current ? '...' : 'OK'"></span>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-800" x-text="item.label"></p>
                                <p class="mt-0.5 text-[11px] leading-4 text-slate-500" x-text="item.message"></p>
                            </div>
                        </div>
                    </template>
                </div>
                <p class="mt-4 text-center text-[11px] text-slate-400">Jangan tutup halaman sampai hasil siap.</p>
            </div>
        </div>
    </div>

    <div x-show="referenceEditor.open" x-cloak class="fixed inset-0 z-50 bg-slate-950/85 backdrop-blur-sm flex items-center justify-center p-0 sm:p-5">
        <div class="flex h-full w-full max-w-6xl flex-col overflow-hidden bg-white shadow-2xl sm:h-auto sm:max-h-[94vh] sm:rounded-2xl sm:border sm:border-white/70">
            <div class="bg-slate-950 px-5 py-4 text-white flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold text-red-300">Koreksi benda patokan</p>
                    <h3 class="text-lg font-black mt-0.5" x-text="`Atur kotak merah pada ${poseLabel(referenceEditor.pose)}`"></h3>
                    <p class="text-xs text-slate-300 mt-1">Kotak sudah diarahkan otomatis. Pastikan keempat sisinya tepat menempel pada tepi A4/KTP.</p>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                    <button type="button" @click="toggleReferenceContrast()"
                        class="min-h-11 touch-manipulation rounded-lg border px-3 py-2 text-xs font-bold"
                        :class="referenceEditor.contrast ? 'border-amber-300 bg-amber-300 text-slate-950' : 'border-white/20 bg-white/10 text-white'">
                        <span x-text="referenceEditor.contrast ? 'Kontras aktif' : 'Naikkan kontras'"></span>
                    </button>
                    <button type="button" @click="createManualReferenceBox(referenceEditor.pose, 'editor')" class="min-h-11 touch-manipulation rounded-lg bg-red-500 px-3 py-2 text-xs font-bold text-white">
                        Deteksi ulang
                    </button>
                    <button type="button" @click="closeReferenceEditor()" class="col-span-2 min-h-11 touch-manipulation rounded-lg bg-white/10 px-3 py-2 text-xs font-bold text-white hover:bg-white/20 sm:col-span-1">
                        Tutup
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-auto bg-slate-100 p-2 sm:p-5">
                <div class="mx-auto max-w-5xl rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                    <canvas x-ref="referenceEditorCanvas"
                        class="block w-full max-h-[72dvh] cursor-move touch-none rounded-lg bg-slate-50 sm:max-h-[68vh]"
                        @mousedown="startManualReference($event, referenceEditor.pose, 'editor')"
                        @mousemove="updateManualReference($event, referenceEditor.pose, 'editor')"
                        @mouseup="finishManualReference($event, referenceEditor.pose, 'editor')"
                        @mouseleave="cancelManualReference(referenceEditor.pose, 'editor')"
                        @touchstart.prevent="startManualReference($event, referenceEditor.pose, 'editor')"
                        @touchmove.prevent="updateManualReference($event, referenceEditor.pose, 'editor')"
                        @touchend.prevent="finishManualReference($event, referenceEditor.pose, 'editor')"></canvas>
                </div>
                <div x-show="referenceEditor.contrast" x-cloak class="mx-auto mt-3 max-w-5xl rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-900">
                    Area di dalam kotak merah sedang ditampilkan dalam grayscale dengan kontras diperkuat. Cocokkan kotak pada empat tepi benda yang terlihat, bukan pada bayangan atau ruang kosong.
                </div>
                <div class="mx-auto mt-3 max-w-5xl grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <div class="rounded-xl border border-red-100 bg-red-50 p-3 text-xs text-red-800">
                        <span class="font-black">1.</span> Cocokkan kotak merah dengan A4/KTP.
                    </div>
                    <div class="rounded-xl border border-red-100 bg-red-50 p-3 text-xs text-red-800">
                        <span class="font-black">2.</span> Tarik sudut kotak untuk menyesuaikan ukuran.
                    </div>
                    <div class="rounded-xl border border-green-100 bg-green-50 p-3 text-xs text-green-800">
                        <span class="font-black">3.</span> Area otomatis tersimpan saat kotak berubah.
                    </div>
                </div>
            </div>

            <div class="sticky bottom-0 border-t border-slate-200 bg-white px-4 py-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));">
                <p class="text-xs text-slate-500" x-text="manualReferenceBoxes[referenceEditor.pose] ? 'Kotak manual aktif dan akan dipakai untuk menghitung skala.' : 'Belum ada kotak manual untuk foto ini.'"></p>
                <div class="grid grid-cols-2 gap-2 sm:flex">
                    <button type="button" @click="clearManualReferenceBox(referenceEditor.pose)" class="min-h-11 touch-manipulation rounded-lg border border-red-100 px-4 py-2 text-xs font-bold text-red-600">
                        Hapus kotak
                    </button>
                    <button type="button" @click="closeReferenceEditor()" class="min-h-11 touch-manipulation rounded-lg bg-red-600 px-4 py-2 text-xs font-bold text-white">
                        Simpan kotak merah
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function measurementCapture() {
        return {
            refObject: '{{ old('ref_object', 'a4') }}',
            referenceMode: '{{ old('reference_mode', 'fixed') }}',
            activePose: 'front',
            cameraReady: false,
            cameraError: '',
            cameraFacing: 'environment',
            stream: null,
            detectionTimer: null,
            poseLandmarker: null,
            poseDetectorReady: false,
            poseDetectorError: '',
            poseDetectionBusy: false,
            isAnalyzing: false,
            analysisProgress: {
                stage: 'idle',
                percent: 0,
                message: '',
                view: null,
            },
            analysisTimeline: [],
            analysisViews: { front: 'waiting', side: 'waiting', back: 'waiting' },
            analysisStartUrl: @json(route('user.measurement.analysis-start')),
            maxFileSizeMb: 5,
            maxTotalSizeMb: 15,
            uploadErrors: {},
            totalUploadError: '',
            previews: { front: null, side: null, back: null },
            detectionReports: { front: null, side: null, back: null },
            manualReferenceBoxes: { front: null, side: null, back: null },
            previewImageData: { front: null, side: null, back: null },
            previewImageSource: { front: null, side: null, back: null },
            referenceDrag: null,
            referenceAction: null,
            referenceEditor: { open: false, pose: 'front', imageData: null, contrast: true },
            liveReport: {
                ready: false,
                checks: [
                    { label: 'Aktifkan kamera untuk mulai deteksi.', ok: false },
                ],
            },
            analysisStageLabels: {
                uploading: 'Mengunggah dan memeriksa foto',
                queued: 'Menyiapkan layanan analisis',
                prepare_photos: 'Menyiapkan resolusi foto',
                reference_roi: 'Mendeteksi tepi A4/KTP',
                body_segmentation: 'Membaca pose dan siluet tubuh',
                cross_view_scale: 'Memeriksa skala tiga foto',
                calculate_measurements: 'Menghitung ukuran tubuh',
                anatomical_validation: 'Memeriksa konsistensi anatomi',
                confidence: 'Menghitung kualitas hasil',
                reconnecting: 'Menghubungkan kembali',
                completed: 'Analisis selesai',
            },
            poseList: [
                { key: 'front', label: 'Foto Depan', hint: 'Badan menghadap kamera, A4/KTP di sisi tubuh.' },
                { key: 'side', label: 'Foto Samping', hint: 'Badan menghadap kiri/kanan, A4/KTP tetap terlihat.' },
                { key: 'back', label: 'Foto Belakang', hint: 'Punggung menghadap kamera, A4/KTP di sisi tubuh.' },
            ],
            get activePoseLabel() {
                return this.poseList.find((pose) => pose.key === this.activePose)?.label || 'Foto Depan';
            },
            async init() {
                this.syncReferenceMode();
                window.addEventListener('beforeunload', () => this.stopCamera());
                await this.initializePoseDetector();
            },
            async initializePoseDetector() {
                if (!window.MediaPipeVision) {
                    this.poseDetectorError = 'Modul deteksi pose belum termuat.';
                    return;
                }

                try {
                    const vision = await window.MediaPipeVision.FilesetResolver.forVisionTasks(
                        '{{ url('/ukur-badan/mediapipe') }}'
                    );
                    this.poseLandmarker = await window.MediaPipeVision.PoseLandmarker.createFromOptions(vision, {
                        baseOptions: {
                            modelAssetPath: '{{ route('user.measurement.pose-model') }}',
                        },
                        runningMode: 'IMAGE',
                        numPoses: 1,
                        minPoseDetectionConfidence: 0.55,
                        minPosePresenceConfidence: 0.55,
                        outputSegmentationMasks: true,
                    });
                    this.poseDetectorReady = true;
                    this.poseDetectorError = '';
                } catch (error) {
                    console.error('Pose detector gagal dimuat', error);
                    this.poseDetectorReady = false;
                    this.poseDetectorError = 'Detektor pose belum siap. Foto tetap akan diperiksa ulang oleh server.';
                }
            },
            detectPoseLandmarks(source) {
                if (!this.poseLandmarker || !source) return null;
                try {
                    const result = this.poseLandmarker.detect(source);
                    const mask = result?.segmentationMasks?.[0];
                    const silhouette = mask ? this.extractSilhouette(mask) : null;
                    if (mask?.close) mask.close();
                    return {
                        landmarks: result?.landmarks?.[0] || null,
                        silhouette,
                    };
                } catch (error) {
                    console.warn('Pose tidak dapat dibaca pada frame', error);
                    return null;
                }
            },
            extractSilhouette(mask) {
                const values = mask.getAsFloat32Array();
                const maskWidth = mask.width;
                const maskHeight = mask.height;
                if (!values?.length || !maskWidth || !maskHeight) return null;

                const left = [];
                const right = [];
                const rgba = new Uint8ClampedArray(maskWidth * maskHeight * 4);
                const rowStep = Math.max(2, Math.round(maskHeight / 90));
                for (let y = 0; y < maskHeight; y++) {
                    let minX = -1;
                    let maxX = -1;
                    for (let x = 0; x < maskWidth; x++) {
                        const probability = values[y * maskWidth + x];
                        if (probability >= 0.42) {
                            const index = (y * maskWidth + x) * 4;
                            rgba[index] = 14;
                            rgba[index + 1] = 165;
                            rgba[index + 2] = 233;
                            rgba[index + 3] = Math.round(Math.min(0.42, probability * 0.38) * 255);
                        }
                        if (probability < 0.5) continue;
                        if (minX < 0) minX = x;
                        maxX = x;
                    }
                    if (y % rowStep === 0 && minX >= 0 && maxX > minX) {
                        left.push({ x: minX / maskWidth, y: y / maskHeight });
                        right.push({ x: maxX / maskWidth, y: y / maskHeight });
                    }
                }
                return left.length >= 8 ? { left, right, maskWidth, maskHeight, rgba } : null;
            },
            landmarkPoint(landmarks, index) {
                const point = landmarks?.[index];
                if (!point) return null;
                const confidence = point.visibility ?? point.presence ?? 1;
                if (confidence < 0.25) return null;
                return {
                    x: Math.max(0, Math.min(1, point.x)),
                    y: Math.max(0, Math.min(1, point.y)),
                };
            },
            buildLandmarkBodyShape(landmarks) {
                if (!Array.isArray(landmarks) || landmarks.length < 29) return null;

                const midpoint = (a, b) => (a && b ? { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 } : null);
                const nose = this.landmarkPoint(landmarks, 0);
                const leftShoulder = this.landmarkPoint(landmarks, 11);
                const rightShoulder = this.landmarkPoint(landmarks, 12);
                const leftElbow = this.landmarkPoint(landmarks, 13);
                const rightElbow = this.landmarkPoint(landmarks, 14);
                const leftWrist = this.landmarkPoint(landmarks, 15);
                const rightWrist = this.landmarkPoint(landmarks, 16);
                const leftHip = this.landmarkPoint(landmarks, 23);
                const rightHip = this.landmarkPoint(landmarks, 24);
                const leftKnee = this.landmarkPoint(landmarks, 25);
                const rightKnee = this.landmarkPoint(landmarks, 26);
                const leftAnkle = this.landmarkPoint(landmarks, 27);
                const rightAnkle = this.landmarkPoint(landmarks, 28);
                const shoulderMid = midpoint(leftShoulder, rightShoulder);
                const hipMid = midpoint(leftHip, rightHip);

                if (!nose || !leftShoulder || !rightShoulder || !leftHip || !rightHip || !shoulderMid || !hipMid) {
                    return null;
                }

                return {
                    head: nose,
                    torso: [leftShoulder, rightShoulder, rightHip, leftHip],
                    bones: [
                        [nose, shoulderMid],
                        [shoulderMid, hipMid],
                        [leftShoulder, leftElbow],
                        [leftElbow, leftWrist],
                        [rightShoulder, rightElbow],
                        [rightElbow, rightWrist],
                        [leftHip, leftKnee],
                        [leftKnee, leftAnkle],
                        [rightHip, rightKnee],
                        [rightKnee, rightAnkle],
                    ].filter(([a, b]) => a && b),
                    joints: [
                        ['nose', nose],
                        ['leftShoulder', leftShoulder],
                        ['rightShoulder', rightShoulder],
                        ['leftElbow', leftElbow],
                        ['rightElbow', rightElbow],
                        ['leftWrist', leftWrist],
                        ['rightWrist', rightWrist],
                        ['leftHip', leftHip],
                        ['rightHip', rightHip],
                        ['leftKnee', leftKnee],
                        ['rightKnee', rightKnee],
                        ['leftAnkle', leftAnkle],
                        ['rightAnkle', rightAnkle],
                    ].filter(([, point]) => Boolean(point)).map(([name, point]) => ({ name, ...point })),
                };
            },
            syncReferenceMode() {
                if (this.refObject === 'ktp' && this.referenceMode === 'handheld') {
                    this.referenceMode = 'fixed';
                }
            },
            setPose(pose) {
                this.activePose = pose;
            },
            async setCameraFacing(facing) {
                this.cameraFacing = facing;
                if (this.cameraReady) {
                    await this.startCamera();
                }
            },
            async startAnalysis(event) {
                if (this.isAnalyzing) return;

                this.validateSelectedFiles(true);
                this.validateDetectionReports();
                if (Object.keys(this.uploadErrors).length > 0 || this.totalUploadError) {
                    this.isAnalyzing = false;
                    this.$nextTick(() => this.scrollToFirstAnalysisError());
                    return;
                }

                this.isAnalyzing = true;
                this.analysisTimeline = [];
                this.analysisViews = { front: 'waiting', side: 'waiting', back: 'waiting' };
                this.recordAnalysisProgress({
                    stage: 'uploading',
                    percent: 1,
                    message: 'Mengunggah tiga foto dan menjalankan validasi awal',
                    view: null,
                });
                this.stopCamera();

                try {
                    const formData = new FormData(event.currentTarget);
                    const response = await fetch(this.analysisStartUrl, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        this.applyServerAnalysisErrors(payload);
                        throw new Error(payload.message || 'Analisis tidak dapat dimulai.');
                    }

                    this.recordAnalysisProgress({
                        stage: 'queued',
                        percent: 3,
                        message: 'Foto diterima dan proses CV sedang disiapkan',
                        view: null,
                    });
                    await this.pollAnalysis(payload.status_url);
                } catch (error) {
                    console.error('Analisis gagal', error);
                    this.isAnalyzing = false;
                    if (!this.totalUploadError) {
                        this.totalUploadError = error.message || 'Analisis gagal. Periksa koneksi lalu coba lagi.';
                    }
                    this.$nextTick(() => this.scrollToFirstAnalysisError());
                }
            },
            async pollAnalysis(statusUrl) {
                const deadline = Date.now() + 4 * 60 * 1000;
                while (Date.now() < deadline) {
                    await new Promise((resolve) => setTimeout(resolve, 850));
                    const response = await fetch(statusUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(payload.message || 'Status analisis tidak dapat dibaca.');
                    }

                    if (payload.progress) this.recordAnalysisProgress(payload.progress);
                    if (payload.status === 'completed' && payload.result_url) {
                        window.location.assign(payload.result_url);
                        return;
                    }
                    if (payload.status === 'failed') {
                        this.applyAnalysisFailure(payload);
                        throw new Error(payload.error || 'Foto tidak dapat dianalisis.');
                    }
                }

                throw new Error('Analisis melewati batas waktu. Coba gunakan foto beresolusi lebih kecil.');
            },
            recordAnalysisProgress(progress) {
                const nextPercent = Math.max(
                    Number(this.analysisProgress.percent || 0),
                    Number(progress.percent || 0),
                );
                this.analysisProgress = { ...progress, percent: nextPercent };
                this.analysisTimeline = this.analysisTimeline.map((item) => ({ ...item, current: false }));

                const existingIndex = this.analysisTimeline.findIndex((item) => item.stage === progress.stage);
                const item = {
                    stage: progress.stage,
                    label: this.analysisStageLabels[progress.stage] || 'Memproses foto',
                    message: progress.message || '',
                    current: progress.stage !== 'completed',
                };
                if (existingIndex >= 0) {
                    this.analysisTimeline.splice(existingIndex, 1, item);
                } else {
                    this.analysisTimeline.push(item);
                }
                this.analysisTimeline = [...this.analysisTimeline];

                if (progress.view) {
                    Object.keys(this.analysisViews).forEach((view) => {
                        if (this.analysisViews[view] === 'active' && view !== progress.view) {
                            this.analysisViews[view] = 'done';
                        }
                    });
                    this.analysisViews[progress.view] = 'active';
                }
                if (nextPercent >= 64) {
                    this.analysisViews = { front: 'done', side: 'done', back: 'done' };
                } else {
                    this.analysisViews = { ...this.analysisViews };
                }
            },
            analysisViewState(view) {
                return this.analysisViews[view] || 'waiting';
            },
            analysisViewLabel(view) {
                return {
                    waiting: 'Menunggu',
                    active: 'Diproses',
                    done: 'Selesai',
                }[this.analysisViewState(view)];
            },
            applyServerAnalysisErrors(payload) {
                const nextErrors = { ...this.uploadErrors };
                Object.entries(payload.errors || {}).forEach(([field, messages]) => {
                    const pose = field.replace('_photo', '');
                    if (['front', 'side', 'back'].includes(pose)) {
                        nextErrors[pose] = Array.isArray(messages) ? messages[0] : messages;
                    }
                });
                this.uploadErrors = nextErrors;
                this.totalUploadError = (payload.photo_issues || []).join(' ') || payload.message || '';
            },
            applyAnalysisFailure(payload) {
                const detail = payload.result || payload;
                const failedView = detail.failed_view;
                const message = detail.error || payload.error || 'Foto tidak dapat dianalisis.';
                if (['front', 'side', 'back'].includes(failedView)) {
                    const label = this.poseLabel(failedView);
                    const edgeHint = detail.reference_processing?.refined === false
                        ? ' Tepi benda belum ditemukan otomatis di dalam kotak. Buka mode kontras dan rapatkan kotak ke empat tepi A4/KTP.'
                        : '';
                    this.uploadErrors = {
                        ...this.uploadErrors,
                        [failedView]: `${label}: ${message}${edgeHint}`,
                    };
                    this.totalUploadError = `${label} perlu diperbaiki. Gunakan tombol koreksi pada kartu yang diberi garis merah.`;
                    return;
                }
                this.totalUploadError = message;
            },
            scrollToFirstAnalysisError() {
                const firstInvalidPose = this.poseList.find((pose) => this.uploadErrors[pose.key]);
                const input = firstInvalidPose ? this.$refs[`${firstInvalidPose.key}Input`] : null;
                const target = firstInvalidPose
                    ? this.$refs.analysisForm?.querySelector(`[data-upload-card="${firstInvalidPose.key}"]`)
                    : this.$refs.analysisForm;
                target?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            },
            validateDetectionReports() {
                const nextErrors = { ...this.uploadErrors };

                this.poseList.forEach((pose) => {
                    const file = this.$refs[`${pose.key}Input`]?.files?.[0];
                    const report = this.detectionReports[pose.key];
                    if (!file || nextErrors[pose.key]) return;
                    if (!report) {
                        nextErrors[pose.key] = `${pose.label}: pemeriksaan foto belum selesai. Tunggu preview dan kotak patokan muncul.`;
                        return;
                    }

                    if (this.poseDetectorReady && !report.poseDetected) {
                        nextErrors[pose.key] = `${pose.label}: orang tidak terdeteksi. Gunakan foto tubuh penuh yang jelas.`;
                        return;
                    }

                    if (this.poseDetectorReady && !report.fullBody) {
                        nextErrors[pose.key] = `${pose.label}: kepala atau kaki belum masuk penuh di dalam foto.`;
                        return;
                    }

                    const manualBox = this.manualReferenceBoxes[pose.key];
                    if (manualBox && !this.referenceBoxRatioOk(manualBox)) {
                        nextErrors[pose.key] = `${pose.label}: kotak ${this.refObject.toUpperCase()} kurang proporsional. Sistem tetap bisa menghitung dari bentuk tubuh, tapi patokan ini tidak dipakai sebagai skala utama.`;
                    }
                });

                this.uploadErrors = nextErrors;
            },
            async startCamera() {
                this.cameraError = '';
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    this.cameraError = 'Browser tidak mendukung akses kamera.';
                    return;
                }

                try {
                    this.stopCamera();
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: { ideal: this.cameraFacing },
                            width: { ideal: 1280 },
                            height: { ideal: 960 },
                        },
                        audio: false,
                    });
                    this.$refs.video.srcObject = this.stream;
                    await this.$refs.video.play();
                    this.cameraReady = true;
                    this.startLiveDetection();
                } catch (error) {
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                width: { ideal: 1280 },
                                height: { ideal: 960 },
                            },
                            audio: false,
                        });
                        this.$refs.video.srcObject = this.stream;
                        await this.$refs.video.play();
                        this.cameraReady = true;
                        this.cameraError = '';
                        this.startLiveDetection();
                    } catch (fallbackError) {
                        this.cameraReady = false;
                        this.cameraError = this.cameraFacing === 'environment'
                            ? 'Kamera belakang tidak tersedia atau akses kamera ditolak.'
                            : 'Kamera depan tidak tersedia atau akses kamera ditolak.';
                    }
                }
            },
            stopCamera() {
                if (this.detectionTimer) {
                    clearInterval(this.detectionTimer);
                    this.detectionTimer = null;
                }
                if (this.stream) {
                    this.stream.getTracks().forEach((track) => track.stop());
                    this.stream = null;
                }
                this.cameraReady = false;
            },
            startLiveDetection() {
                if (this.detectionTimer) clearInterval(this.detectionTimer);
                this.detectionTimer = setInterval(async () => {
                    if (!this.cameraReady || !this.$refs.video?.videoWidth || this.poseDetectionBusy) return;
                    this.poseDetectionBusy = true;

                    try {
                        const video = this.$refs.video;
                        const canvas = this.$refs.canvas;
                        canvas.width = 320;
                        canvas.height = Math.max(1, Math.round(320 * (video.videoHeight / video.videoWidth)));
                        const ctx = canvas.getContext('2d', { willReadFrequently: true });
                        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                        const poseAnalysis = this.detectPoseLandmarks(canvas);
                        this.liveReport = this.buildDetectionReport(ctx, canvas.width, canvas.height, this.activePose, false, poseAnalysis);
                    } finally {
                        this.poseDetectionBusy = false;
                    }
                }, 900);
            },
            async capturePose(pose) {
                if (!this.cameraReady || !this.liveReport.captureReady || !this.$refs.video.videoWidth) return;

                const video = this.$refs.video;
                const canvas = this.$refs.canvas;
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.92));
                if (!blob) return;

                const file = new File([blob], `${pose}-measurement-${Date.now()}.jpg`, { type: 'image/jpeg' });
                const transfer = new DataTransfer();
                transfer.items.add(file);
                const input = this.$refs[`${pose}Input`];
                input.files = transfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));

                const currentIndex = this.poseList.findIndex((item) => item.key === pose);
                const nextPose = this.poseList[currentIndex + 1];
                if (nextPose) this.activePose = nextPose.key;
            },
            handleUpload(event, pose) {
                const file = event.target.files[0];
                if (!file) {
                    this.previews[pose] = null;
                    this.detectionReports[pose] = null;
                    this.manualReferenceBoxes[pose] = null;
                    this.previewImageData[pose] = null;
                    this.previewImageSource[pose] = null;
                    delete this.uploadErrors[pose];
                    this.uploadErrors = { ...this.uploadErrors };
                    this.validateSelectedFiles();
                    return;
                }

                delete this.uploadErrors[pose];
                if (!file.type.startsWith('image/')) {
                    this.uploadErrors[pose] = `${this.poseLabel(pose)} harus berupa file gambar.`;
                } else if (file.size > this.maxFileSizeMb * 1024 * 1024) {
                    this.uploadErrors[pose] = `${this.poseLabel(pose)} terlalu besar (${this.formatMb(file.size)}MB). Maksimal ${this.maxFileSizeMb}MB per foto.`;
                }
                this.uploadErrors = { ...this.uploadErrors };

                if (this.uploadErrors[pose]) {
                    event.target.value = '';
                    if (this.previews[pose]) URL.revokeObjectURL(this.previews[pose]);
                    this.previews[pose] = null;
                    this.detectionReports[pose] = null;
                    this.manualReferenceBoxes[pose] = null;
                    this.previewImageData[pose] = null;
                    this.previewImageSource[pose] = null;
                    return;
                }

                if (this.previews[pose]) URL.revokeObjectURL(this.previews[pose]);
                this.previews[pose] = URL.createObjectURL(file);
                this.manualReferenceBoxes[pose] = null;
                this.validateSelectedFiles();
                this.analyzePreview(file, pose);
            },
            validateSelectedFiles(requireAll = false) {
                const nextErrors = {};
                let totalSize = 0;

                this.poseList.forEach((pose) => {
                    const input = this.$refs[`${pose.key}Input`];
                    const file = input?.files?.[0];
                    if (!file) {
                        if (requireAll) nextErrors[pose.key] = `${pose.label} wajib dipilih sebelum analisis.`;
                        return;
                    }

                    totalSize += file.size;
                    if (!file.type.startsWith('image/')) {
                        nextErrors[pose.key] = `${pose.label} harus berupa file gambar.`;
                    } else if (file.size > this.maxFileSizeMb * 1024 * 1024) {
                        nextErrors[pose.key] = `${pose.label} terlalu besar (${this.formatMb(file.size)}MB). Maksimal ${this.maxFileSizeMb}MB per foto.`;
                    }
                });

                if (totalSize > this.maxTotalSizeMb * 1024 * 1024) {
                    this.totalUploadError = `Total ukuran 3 foto terlalu besar (${this.formatMb(totalSize)}MB). Maksimal sekitar ${this.maxTotalSizeMb}MB untuk sekali analisis.`;
                } else {
                    this.totalUploadError = '';
                }

                this.uploadErrors = nextErrors;
                return Object.keys(this.uploadErrors).length === 0 && !this.totalUploadError;
            },
            poseLabel(pose) {
                return this.poseList.find((item) => item.key === pose)?.label || 'Foto';
            },
            formatMb(bytes) {
                return (bytes / 1024 / 1024).toFixed(1);
            },
            analyzePreview(file, pose) {
                const image = new Image();
                image.onload = async () => {
                    const canvas = this.$refs[`${pose}DetectionCanvas`];
                    if (!canvas) return;

                    const maxWidth = 520;
                    const scale = Math.min(1, maxWidth / image.width);
                    canvas.width = Math.max(1, Math.round(image.width * scale));
                    canvas.height = Math.max(1, Math.round(image.height * scale));
                    const ctx = canvas.getContext('2d', { willReadFrequently: true });
                    ctx.drawImage(image, 0, 0, canvas.width, canvas.height);

                    const poseAnalysis = this.detectPoseLandmarks(canvas);
                    const report = this.buildDetectionReport(ctx, canvas.width, canvas.height, pose, true, poseAnalysis);
                    this.previewImageSource[pose] = image;
                    this.previewImageSource = { ...this.previewImageSource };
                    this.previewImageData[pose] = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    this.previewImageData = { ...this.previewImageData };
                    this.detectionReports[pose] = report;
                    this.detectionReports = { ...this.detectionReports };
                    if (report.refBox && !this.manualReferenceBoxes[pose]) {
                        this.setReferenceBox(pose, report.refBox, canvas);
                    }
                    this.redrawPreviewOverlay(pose);
                    URL.revokeObjectURL(image.src);
                };
                image.src = URL.createObjectURL(file);
            },
            getReferenceCanvas(pose, surface = 'preview') {
                if (surface === 'editor') return this.$refs.referenceEditorCanvas;
                return this.$refs[`${pose}DetectionCanvas`];
            },
            canvasPoint(event, canvas) {
                const source = event.touches?.[0] || event.changedTouches?.[0] || event;
                const rect = canvas.getBoundingClientRect();
                return {
                    x: (source.clientX - rect.left) * (canvas.width / rect.width),
                    y: (source.clientY - rect.top) * (canvas.height / rect.height),
                };
            },
            startManualReference(event, pose, surface = 'preview') {
                const canvas = this.getReferenceCanvas(pose, surface);
                if (!canvas || !this.referenceCanvasReady(pose, surface)) return;

                const point = this.canvasPoint(event, canvas);
                const box = this.scaleReferenceBoxToCanvas(this.manualReferenceBoxes[pose], canvas);
                if (box) {
                    this.referenceAction = this.pickReferenceAction(point, box);
                    if (this.referenceAction) {
                        this.referenceDrag = {
                            pose,
                            surface,
                            startX: point.x,
                            startY: point.y,
                            currentX: point.x,
                            currentY: point.y,
                            originalBox: { ...box },
                        };
                        this.redrawPreviewOverlay(pose);
                        return;
                    }
                }

                this.referenceAction = 'draw';
                this.referenceDrag = { pose, surface, startX: point.x, startY: point.y, currentX: point.x, currentY: point.y };
                this.redrawReferenceCanvas(pose, surface);
            },
            updateManualReference(event, pose, surface = 'preview') {
                if (!this.referenceDrag || this.referenceDrag.pose !== pose || this.referenceDrag.surface !== surface) return;
                const canvas = this.getReferenceCanvas(pose, surface);
                const point = this.canvasPoint(event, canvas);
                this.referenceDrag.currentX = point.x;
                this.referenceDrag.currentY = point.y;

                if (this.referenceAction === 'move' || this.referenceAction?.startsWith('resize-')) {
                    const nextBox = this.boxFromEditAction(this.referenceDrag, this.referenceAction);
                    if (nextBox) {
                        this.manualReferenceBoxes[pose] = nextBox;
                        this.manualReferenceBoxes = { ...this.manualReferenceBoxes };
                    }
                }
                this.redrawReferenceCanvas(pose, surface);
            },
            finishManualReference(event, pose, surface = 'preview') {
                if (!this.referenceDrag || this.referenceDrag.pose !== pose || this.referenceDrag.surface !== surface) return;
                this.updateManualReference(event, pose, surface);
                const box = this.referenceAction === 'draw'
                    ? this.normalizeReferenceBox(this.referenceDrag)
                    : this.manualReferenceBoxes[pose];
                this.referenceDrag = null;
                this.referenceAction = null;

                if (box && box.w >= 12 && box.h >= 12) {
                    this.manualReferenceBoxes[pose] = box;
                    this.manualReferenceBoxes = { ...this.manualReferenceBoxes };
                    this.syncReferenceReport(pose);
                }
                this.redrawReferenceCanvas(pose, surface);
                if (surface === 'editor') this.redrawPreviewOverlay(pose);
            },
            cancelManualReference(pose, surface = 'preview') {
                if (this.referenceDrag?.pose !== pose || this.referenceDrag?.surface !== surface) return;
                this.referenceDrag = null;
                this.referenceAction = null;
                this.redrawReferenceCanvas(pose, surface);
            },
            createManualReferenceBox(pose, surface = 'preview') {
                const canvas = this.getReferenceCanvas(pose, surface);
                if (!canvas || !this.referenceCanvasReady(pose, surface)) return;

                const detected = this.detectionReports[pose]?.refBox;
                if (detected) {
                    const sourceCanvas = this.$refs[`${pose}DetectionCanvas`];
                    const detectedForCanvas = this.scaleReferenceBoxToCanvas({
                        ...detected,
                        image_width: sourceCanvas?.width || canvas.width,
                        image_height: sourceCanvas?.height || canvas.height,
                    }, canvas);
                    this.setReferenceBox(pose, detectedForCanvas, canvas);
                    this.redrawReferenceCanvas(pose, surface);
                    return;
                }

                const ratio = this.refObject === 'ktp' ? 8.56 / 5.398 : 21 / 29.7;
                const h = Math.round(canvas.height * 0.22);
                const w = Math.round(h * ratio);
                this.manualReferenceBoxes[pose] = {
                    x: Math.round(canvas.width * 0.68),
                    y: Math.round(canvas.height * 0.16),
                    w: Math.max(24, Math.min(w, canvas.width * 0.28)),
                    h: Math.max(36, h),
                    image_width: canvas.width,
                    image_height: canvas.height,
                };
                this.manualReferenceBoxes = { ...this.manualReferenceBoxes };
                this.redrawReferenceCanvas(pose, surface);
                if (surface === 'editor') this.redrawPreviewOverlay(pose);
            },
            normalizeReferenceBox(drag) {
                const canvas = this.getReferenceCanvas(drag.pose, drag.surface);
                if (!canvas) return null;
                const x = Math.max(0, Math.min(drag.startX, drag.currentX));
                const y = Math.max(0, Math.min(drag.startY, drag.currentY));
                const w = Math.min(canvas.width - x, Math.abs(drag.currentX - drag.startX));
                const h = Math.min(canvas.height - y, Math.abs(drag.currentY - drag.startY));
                return {
                    x: Math.round(x),
                    y: Math.round(y),
                    w: Math.round(w),
                    h: Math.round(h),
                    image_width: canvas.width,
                    image_height: canvas.height,
                };
            },
            pickReferenceAction(point, box) {
                const handle = Math.max(14, box.image_width * 0.025);
                const handles = {
                    'resize-nw': { x: box.x, y: box.y },
                    'resize-ne': { x: box.x + box.w, y: box.y },
                    'resize-sw': { x: box.x, y: box.y + box.h },
                    'resize-se': { x: box.x + box.w, y: box.y + box.h },
                };

                for (const [action, pos] of Object.entries(handles)) {
                    if (Math.abs(point.x - pos.x) <= handle && Math.abs(point.y - pos.y) <= handle) {
                        return action;
                    }
                }

                const inside = point.x >= box.x && point.x <= box.x + box.w && point.y >= box.y && point.y <= box.y + box.h;
                return inside ? 'move' : null;
            },
            boxFromEditAction(drag, action) {
                const canvas = this.getReferenceCanvas(drag.pose, drag.surface);
                const box = { ...drag.originalBox };
                const dx = drag.currentX - drag.startX;
                const dy = drag.currentY - drag.startY;
                const minSize = 16;

                if (action === 'move') {
                    box.x = Math.max(0, Math.min(canvas.width - box.w, box.x + dx));
                    box.y = Math.max(0, Math.min(canvas.height - box.h, box.y + dy));
                } else {
                    let x1 = box.x;
                    let y1 = box.y;
                    let x2 = box.x + box.w;
                    let y2 = box.y + box.h;

                    if (action.includes('n')) y1 = Math.max(0, Math.min(y2 - minSize, y1 + dy));
                    if (action.includes('s')) y2 = Math.min(canvas.height, Math.max(y1 + minSize, y2 + dy));
                    if (action.includes('w')) x1 = Math.max(0, Math.min(x2 - minSize, x1 + dx));
                    if (action.includes('e')) x2 = Math.min(canvas.width, Math.max(x1 + minSize, x2 + dx));

                    box.x = x1;
                    box.y = y1;
                    box.w = x2 - x1;
                    box.h = y2 - y1;
                }

                return {
                    x: Math.round(box.x),
                    y: Math.round(box.y),
                    w: Math.round(box.w),
                    h: Math.round(box.h),
                    image_width: canvas.width,
                    image_height: canvas.height,
                };
            },
            useDetectedReferenceBox(pose) {
                const refBox = this.detectionReports[pose]?.refBox;
                const canvas = this.$refs[`${pose}DetectionCanvas`];
                if (!refBox || !canvas) return;
                this.setReferenceBox(pose, refBox, canvas);
                this.redrawPreviewOverlay(pose);
                if (this.referenceEditor.open && this.referenceEditor.pose === pose) this.redrawReferenceEditor();
            },
            setReferenceBox(pose, box, canvas) {
                if (!box || !canvas) return;
                this.manualReferenceBoxes[pose] = {
                    x: Math.round(box.x),
                    y: Math.round(box.y),
                    w: Math.round(box.w),
                    h: Math.round(box.h),
                    image_width: canvas.width,
                    image_height: canvas.height,
                };
                this.manualReferenceBoxes = { ...this.manualReferenceBoxes };
                this.syncReferenceReport(pose);
            },
            syncReferenceReport(pose) {
                const report = this.detectionReports[pose];
                const canvas = this.$refs[`${pose}DetectionCanvas`];
                const box = this.scaleReferenceBoxToCanvas(this.manualReferenceBoxes[pose], canvas);
                if (!report || !box || !this.referenceBoxRatioOk(box)) return;

                report.refBox = { x: box.x, y: box.y, w: box.w, h: box.h };
                if (report.checks?.[2]) {
                    report.checks[2] = {
                        label: `${this.refObject.toUpperCase()} ditandai oleh kotak merah.`,
                        ok: true,
                    };
                }
                if (report.checks?.[3]) {
                    report.checks[3] = {
                        label: 'Proporsi kotak merah sesuai benda patokan.',
                        ok: true,
                    };
                }
                report.ready = this.poseDetectorReady && report.checks.every((item) => item.ok);
                this.detectionReports = { ...this.detectionReports };
                delete this.uploadErrors[pose];
                this.uploadErrors = { ...this.uploadErrors };
                if (Object.keys(this.uploadErrors).length === 0) this.totalUploadError = '';
            },
            clearManualReferenceBox(pose) {
                this.manualReferenceBoxes[pose] = null;
                this.manualReferenceBoxes = { ...this.manualReferenceBoxes };
                this.redrawPreviewOverlay(pose);
                if (this.referenceEditor.open && this.referenceEditor.pose === pose) this.redrawReferenceEditor();
            },
            referenceCanvasReady(pose, surface = 'preview') {
                return surface === 'editor'
                    ? Boolean(this.referenceEditor.imageData)
                    : Boolean(this.previewImageData[pose]);
            },
            scaleReferenceBoxToCanvas(box, canvas) {
                if (!box || !canvas || !box.image_width || !box.image_height) return null;
                const scaleX = canvas.width / box.image_width;
                const scaleY = canvas.height / box.image_height;
                return {
                    x: box.x * scaleX,
                    y: box.y * scaleY,
                    w: box.w * scaleX,
                    h: box.h * scaleY,
                    image_width: canvas.width,
                    image_height: canvas.height,
                };
            },
            redrawReferenceCanvas(pose, surface = 'preview') {
                if (surface === 'editor') {
                    this.redrawReferenceEditor();
                    return;
                }
                this.redrawPreviewOverlay(pose);
            },
            openReferenceEditor(pose) {
                if (!this.previewImageSource[pose]) return;
                this.referenceEditor = { open: true, pose, imageData: null, contrast: true };
                this.$nextTick(() => {
                    const image = this.previewImageSource[pose];
                    const canvas = this.$refs.referenceEditorCanvas;
                    if (!canvas || !image) return;

                    const maxWidth = 1100;
                    const scale = Math.min(1, maxWidth / image.width);
                    canvas.width = Math.max(1, Math.round(image.width * scale));
                    canvas.height = Math.max(1, Math.round(image.height * scale));
                    const ctx = canvas.getContext('2d', { willReadFrequently: true });
                    ctx.drawImage(image, 0, 0, canvas.width, canvas.height);
                    this.referenceEditor.imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    if (!this.manualReferenceBoxes[pose]) this.createManualReferenceBox(pose, 'editor');
                    this.redrawReferenceEditor();
                });
            },
            toggleReferenceContrast() {
                this.referenceEditor.contrast = !this.referenceEditor.contrast;
                this.redrawReferenceEditor();
            },
            enhanceReferenceArea(ctx, box) {
                if (!box?.w || !box?.h) return;
                const x = Math.max(0, Math.floor(box.x));
                const y = Math.max(0, Math.floor(box.y));
                const width = Math.max(1, Math.min(ctx.canvas.width - x, Math.ceil(box.w)));
                const height = Math.max(1, Math.min(ctx.canvas.height - y, Math.ceil(box.h)));
                if (width <= 1 || height <= 1) return;

                const image = ctx.getImageData(x, y, width, height);
                const luminance = new Uint8Array(width * height);
                let min = 255;
                let max = 0;
                for (let pixel = 0; pixel < luminance.length; pixel++) {
                    const offset = pixel * 4;
                    const value = Math.round(
                        image.data[offset] * 0.299
                        + image.data[offset + 1] * 0.587
                        + image.data[offset + 2] * 0.114,
                    );
                    luminance[pixel] = value;
                    min = Math.min(min, value);
                    max = Math.max(max, value);
                }

                const range = Math.max(24, max - min);
                for (let pixel = 0; pixel < luminance.length; pixel++) {
                    const offset = pixel * 4;
                    const stretched = Math.max(0, Math.min(255, Math.round((luminance[pixel] - min) * 255 / range)));
                    const highContrast = stretched < 112 ? Math.round(stretched * 0.72) : Math.min(255, Math.round(128 + (stretched - 112) * 1.35));
                    image.data[offset] = highContrast;
                    image.data[offset + 1] = highContrast;
                    image.data[offset + 2] = highContrast;
                }
                ctx.putImageData(image, x, y);
            },
            closeReferenceEditor() {
                const pose = this.referenceEditor.pose;
                this.referenceEditor.open = false;
                this.referenceDrag = null;
                this.referenceAction = null;
                this.redrawPreviewOverlay(pose);
            },
            redrawReferenceEditor() {
                const pose = this.referenceEditor.pose;
                const canvas = this.$refs.referenceEditorCanvas;
                const snapshot = this.referenceEditor.imageData;
                if (!canvas || !snapshot) return;

                const ctx = canvas.getContext('2d', { willReadFrequently: true });
                ctx.putImageData(snapshot, 0, 0);
                const manualBox = this.scaleReferenceBoxToCanvas(this.manualReferenceBoxes[pose], canvas);
                if (this.referenceEditor.contrast && manualBox) {
                    this.enhanceReferenceArea(ctx, manualBox);
                }
                const report = this.detectionReports[pose];
                if (report) {
                    const scaledReport = {
                        ...report,
                        refBox: this.scaleReferenceBoxToCanvas(report.refBox, canvas),
                        bodyBox: this.scaleReferenceBoxToCanvas({ ...report.bodyBox, image_width: this.$refs[`${pose}DetectionCanvas`]?.width || canvas.width, image_height: this.$refs[`${pose}DetectionCanvas`]?.height || canvas.height }, canvas),
                    };
                    this.drawDetectionOverlay(ctx, canvas.width, canvas.height, scaledReport);
                }

                if (manualBox) this.drawManualReferenceBox(ctx, manualBox, '#ef4444', this.refObject.toUpperCase());
                if (this.referenceDrag?.pose === pose && this.referenceDrag?.surface === 'editor' && this.referenceAction === 'draw') {
                    const draft = this.normalizeReferenceBox(this.referenceDrag);
                    if (draft) this.drawManualReferenceBox(ctx, draft, '#ef4444', this.refObject.toUpperCase());
                }
            },
            redrawPreviewOverlay(pose) {
                const canvas = this.$refs[`${pose}DetectionCanvas`];
                const snapshot = this.previewImageData[pose];
                if (!canvas || !snapshot) return;
                const ctx = canvas.getContext('2d', { willReadFrequently: true });
                ctx.putImageData(snapshot, 0, 0);
                const report = this.detectionReports[pose];
                if (report) this.drawDetectionOverlay(ctx, canvas.width, canvas.height, report);
                const manualBox = this.scaleReferenceBoxToCanvas(this.manualReferenceBoxes[pose], canvas);
                if (manualBox) this.drawManualReferenceBox(ctx, manualBox, '#ef4444', this.refObject.toUpperCase());
                if (this.referenceDrag?.pose === pose && this.referenceDrag?.surface === 'preview' && this.referenceAction === 'draw') {
                    const draft = this.normalizeReferenceBox(this.referenceDrag);
                    if (draft) this.drawManualReferenceBox(ctx, draft, '#ef4444', this.refObject.toUpperCase());
                }
            },
            summarizePose(poseLandmarks, width, height, pose) {
                const fallback = {
                    detected: false,
                    fullBody: false,
                    orientationOk: false,
                    bodyBox: {
                        x: width * 0.25,
                        y: height * 0.08,
                        w: width * 0.5,
                        h: height * 0.84,
                    },
                };
                if (!Array.isArray(poseLandmarks) || poseLandmarks.length < 29) return fallback;

                const visible = (index, threshold = 0.45) => {
                    const landmark = poseLandmarks[index];
                    if (!landmark) return false;
                    const confidence = landmark.visibility ?? landmark.presence ?? 1;
                    return confidence >= threshold
                        && landmark.x >= -0.03 && landmark.x <= 1.03
                        && landmark.y >= -0.03 && landmark.y <= 1.03;
                };
                const pairVisible = (left, right) => visible(left) && visible(right);
                const detected = pairVisible(11, 12) && pairVisible(23, 24);
                if (!detected) return fallback;

                const upperBodyVisible = visible(0) && pairVisible(11, 12) && pairVisible(23, 24);
                const leftLegVisible = visible(25, 0.35) && visible(27, 0.35);
                const rightLegVisible = visible(26, 0.35) && visible(28, 0.35);
                const visibleAnkleYs = [27, 28]
                    .filter((index) => visible(index, 0.35))
                    .map((index) => poseLandmarks[index].y);
                const fullBody = upperBodyVisible
                    && (leftLegVisible || rightLegVisible)
                    && poseLandmarks[0].y > 0.005
                    && visibleAnkleYs.length > 0
                    && Math.max(...visibleAnkleYs) < 0.998;

                const visibleLandmarks = poseLandmarks.filter((_, index) => visible(index, 0.35));
                const xs = visibleLandmarks.map((landmark) => landmark.x * width);
                const ys = visibleLandmarks.map((landmark) => landmark.y * height);
                const minX = Math.max(0, Math.min(...xs) - width * 0.04);
                const maxX = Math.min(width, Math.max(...xs) + width * 0.04);
                const minY = Math.max(0, Math.min(...ys) - height * 0.03);
                const maxY = Math.min(height, Math.max(...ys) + height * 0.03);
                const shoulderSpan = Math.abs(poseLandmarks[12].x - poseLandmarks[11].x);
                const orientationOk = pose === 'side'
                    ? shoulderSpan <= 0.18
                    : shoulderSpan >= 0.08;

                return {
                    detected,
                    fullBody,
                    orientationOk,
                    bodyBox: {
                        x: minX,
                        y: minY,
                        w: Math.max(1, maxX - minX),
                        h: Math.max(1, maxY - minY),
                    },
                };
            },
            referenceBoxRatioOk(box) {
                if (!box?.w || !box?.h) return false;
                const ratio = Math.max(box.w, box.h) / Math.max(1, Math.min(box.w, box.h));
                const expected = this.refObject === 'ktp' ? (8.56 / 5.398) : (29.7 / 21);
                return Math.abs(ratio - expected) / expected <= 0.28;
            },
            findReferenceCandidate(imageData, width, height, bodyBox = null) {
                const step = 3;
                const cols = Math.ceil(width / step);
                const rows = Math.ceil(height / step);
                const active = new Uint8Array(cols * rows);
                const visited = new Uint8Array(cols * rows);

                for (let gy = 0; gy < rows; gy++) {
                    const y = Math.min(height - 1, gy * step);
                    for (let gx = 0; gx < cols; gx++) {
                        const x = Math.min(width - 1, gx * step);
                        const offset = (y * width + x) * 4;
                        const r = imageData[offset];
                        const g = imageData[offset + 1];
                        const b = imageData[offset + 2];
                        const luma = (r + g + b) / 3;
                        const neutral = Math.max(r, g, b) - Math.min(r, g, b) < 48;
                        active[gy * cols + gx] = luma > 205 && neutral ? 1 : 0;
                    }
                }

                const candidates = [];
                const stack = [];
                for (let gy = 0; gy < rows; gy++) {
                    for (let gx = 0; gx < cols; gx++) {
                        const start = gy * cols + gx;
                        if (!active[start] || visited[start]) continue;

                        let count = 0;
                        let minX = gx;
                        let maxX = gx;
                        let minY = gy;
                        let maxY = gy;
                        let touchesFrame = false;
                        stack.push(start);
                        visited[start] = 1;

                        while (stack.length) {
                            const current = stack.pop();
                            const cy = Math.floor(current / cols);
                            const cx = current - cy * cols;
                            count++;
                            minX = Math.min(minX, cx);
                            maxX = Math.max(maxX, cx);
                            minY = Math.min(minY, cy);
                            maxY = Math.max(maxY, cy);
                            touchesFrame ||= cx === 0 || cy === 0 || cx === cols - 1 || cy === rows - 1;

                            [[cx - 1, cy], [cx + 1, cy], [cx, cy - 1], [cx, cy + 1]].forEach(([nx, ny]) => {
                                if (nx < 0 || ny < 0 || nx >= cols || ny >= rows) return;
                                const next = ny * cols + nx;
                                if (!active[next] || visited[next]) return;
                                visited[next] = 1;
                                stack.push(next);
                            });
                        }

                        const boxW = (maxX - minX + 1) * step;
                        const boxH = (maxY - minY + 1) * step;
                        const boxArea = boxW * boxH;
                        const areaRatio = boxArea / Math.max(1, width * height);
                        const fillRatio = (count * step * step) / Math.max(1, boxArea);
                        const centerX = ((minX + maxX + 1) * step) / 2;
                        const bodyGap = width * 0.015;
                        const atSide = bodyBox
                            ? (minX * step + boxW < bodyBox.x - bodyGap
                                || minX * step > bodyBox.x + bodyBox.w + bodyGap)
                            : (centerX < width * 0.38 || centerX > width * 0.62);
                        const box = { x: minX * step, y: minY * step, w: boxW, h: boxH };
                        const referenceLongCm = this.refObject === 'ktp' ? 8.56 : 29.7;
                        const impliedStature = bodyBox
                            ? (bodyBox.h / Math.max(boxW, boxH)) * referenceLongCm * 1.08
                            : 165;
                        const scalePlausible = impliedStature >= 110 && impliedStature <= 230;

                        if (!touchesFrame && atSide && areaRatio >= 0.002 && areaRatio <= 0.12
                            && fillRatio >= 0.38 && scalePlausible && this.referenceBoxRatioOk(box)) {
                            candidates.push({
                                ...box,
                                score: fillRatio * 0.55 + (1 - Math.abs(impliedStature - 165) / 165) * 0.45,
                            });
                        }
                    }
                }

                candidates.sort((a, b) => b.score - a.score);
                const candidate = candidates[0];
                if (!candidate) return null;
                return { x: candidate.x, y: candidate.y, w: candidate.w, h: candidate.h };
            },
            buildDetectionReport(ctx, width, height, pose, includeOverlay, poseAnalysis = null) {
                const imageData = ctx.getImageData(0, 0, width, height).data;
                let luminance = 0;
                let contrastCount = 0;

                for (let y = 0; y < height; y += 2) {
                    for (let x = 0; x < width; x += 2) {
                        const index = (y * width + x) * 4;
                        const r = imageData[index];
                        const g = imageData[index + 1];
                        const b = imageData[index + 2];
                        const l = (r + g + b) / 3;
                        luminance += l;
                        if (l < 55 || l > 210) {
                            contrastCount++;
                        }
                    }
                }

                const sampleCount = Math.ceil(width / 2) * Math.ceil(height / 2);
                const avgLight = luminance / Math.max(1, sampleCount);
                const poseSummary = this.summarizePose(poseAnalysis?.landmarks, width, height, pose);
                const refBox = this.findReferenceCandidate(imageData, width, height, poseSummary.bodyBox);
                const lightOk = avgLight > 65 && avgLight < 220;
                const contrastOk = contrastCount > sampleCount * 0.08;
                const sideWarningOk = !(this.referenceMode === 'handheld' && pose === 'side');
                const detectorAvailable = this.poseDetectorReady;

                const checks = [
                    {
                        label: poseSummary.detected
                            ? 'Orang terdeteksi dengan landmark tubuh.'
                            : (detectorAvailable ? 'Orang belum terdeteksi dengan jelas.' : 'Detektor pose sedang disiapkan.'),
                        ok: poseSummary.detected,
                    },
                    { label: poseSummary.fullBody ? 'Kepala sampai kaki masuk penuh.' : 'Mundur agar kepala sampai kaki terlihat penuh.', ok: poseSummary.fullBody },
                    { label: refBox ? `${this.refObject.toUpperCase()} terdeteksi sebagai bidang terpisah.` : `${this.refObject.toUpperCase()} belum terbaca otomatis. Sistem tetap memakai estimasi bentuk tubuh.`, ok: true },
                    { label: refBox ? 'Proporsi benda patokan sesuai.' : 'Patokan menjadi bantuan visual, bukan penghambat proses.', ok: true },
                    { label: lightOk && contrastOk ? 'Pencahayaan cukup untuk dianalisis.' : 'Perbaiki cahaya atau hindari background terlalu datar.', ok: lightOk && contrastOk },
                    {
                        label: poseSummary.orientationOk && sideWarningOk
                            ? `Arah tubuh sesuai ${this.poseLabel(pose).toLowerCase()}.`
                            : (sideWarningOk ? 'Arah tubuh belum sesuai dengan pose yang dipilih.' : 'Mode praktis tidak disarankan pada foto samping.'),
                        ok: poseSummary.orientationOk && sideWarningOk,
                    },
                ];

                return {
                    ready: detectorAvailable && checks.every((item) => item.ok),
                    captureReady: detectorAvailable
                        && poseSummary.detected
                        && poseSummary.fullBody
                        && lightOk
                        && contrastOk
                        && poseSummary.orientationOk
                        && sideWarningOk,
                    checks,
                    refBox,
                    bodyBox: poseSummary.bodyBox,
                    silhouette: poseAnalysis?.silhouette || null,
                    landmarkShape: this.buildLandmarkBodyShape(poseAnalysis?.landmarks),
                    poseDetected: poseSummary.detected,
                    fullBody: poseSummary.fullBody,
                    includeOverlay,
                };
            },
            drawDetectionOverlay(ctx, width, height, report) {
                ctx.save();
                ctx.lineWidth = Math.max(2, width * 0.006);
                ctx.strokeStyle = '#0ea5e9';
                ctx.fillStyle = 'rgba(14, 165, 233, 0.12)';

                if (report.silhouette?.left?.length) {
                    if (report.silhouette.rgba) {
                        const maskCanvas = document.createElement('canvas');
                        maskCanvas.width = report.silhouette.maskWidth;
                        maskCanvas.height = report.silhouette.maskHeight;
                        const maskCtx = maskCanvas.getContext('2d');
                        maskCtx.putImageData(
                            new ImageData(report.silhouette.rgba, report.silhouette.maskWidth, report.silhouette.maskHeight),
                            0,
                            0
                        );
                        ctx.drawImage(maskCanvas, 0, 0, width, height);
                    }

                    ctx.beginPath();
                    report.silhouette.left.forEach((point, index) => {
                        const x = point.x * width;
                        const y = point.y * height;
                        if (index === 0) ctx.moveTo(x, y);
                        else ctx.lineTo(x, y);
                    });
                    [...report.silhouette.right].reverse().forEach((point) => {
                        ctx.lineTo(point.x * width, point.y * height);
                    });
                    ctx.closePath();
                    if (!report.silhouette.rgba) ctx.fill();
                    ctx.stroke();
                } else if (report.landmarkShape) {
                    this.drawLandmarkBodyOverlay(ctx, width, height, report.landmarkShape);
                } else {
                    this.drawPoseGuideOverlay(ctx, width, height, report.bodyBox);
                }
                ctx.restore();
            },
            drawLandmarkBodyOverlay(ctx, width, height, shape) {
                const px = (point) => ({ x: point.x * width, y: point.y * height });
                ctx.save();
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';

                const joints = Object.fromEntries(shape.joints.map((joint) => [joint.name, px(joint)]));
                const shoulderWidth = joints.leftShoulder && joints.rightShoulder
                    ? Math.abs(joints.rightShoulder.x - joints.leftShoulder.x)
                    : width * 0.18;
                const limbWidth = Math.max(10, shoulderWidth * 0.18);
                const legWidth = Math.max(12, shoulderWidth * 0.22);
                const headRadius = Math.max(10, shoulderWidth * 0.22);
                const headCenter = joints.nose
                    ? { x: joints.nose.x, y: joints.nose.y + headRadius * 0.55 }
                    : null;

                const drawCapsule = (points, strokeWidth) => {
                    const validPoints = points.filter(Boolean);
                    if (validPoints.length < 2) return;
                    ctx.beginPath();
                    ctx.moveTo(validPoints[0].x, validPoints[0].y);
                    validPoints.slice(1).forEach((point) => ctx.lineTo(point.x, point.y));
                    ctx.lineWidth = strokeWidth;
                    ctx.strokeStyle = 'rgba(245, 158, 11, 0.24)';
                    ctx.stroke();
                    ctx.lineWidth = Math.max(2, strokeWidth * 0.18);
                    ctx.strokeStyle = '#f59e0b';
                    ctx.stroke();
                };

                if (headCenter) {
                    ctx.beginPath();
                    ctx.ellipse(headCenter.x, headCenter.y, headRadius * 0.74, headRadius, 0, 0, Math.PI * 2);
                    ctx.fillStyle = 'rgba(245, 158, 11, 0.18)';
                    ctx.strokeStyle = '#f59e0b';
                    ctx.lineWidth = Math.max(2, width * 0.004);
                    ctx.fill();
                    ctx.stroke();
                }

                drawCapsule([joints.leftShoulder, joints.leftElbow, joints.leftWrist], limbWidth);
                drawCapsule([joints.rightShoulder, joints.rightElbow, joints.rightWrist], limbWidth);
                drawCapsule([joints.leftHip, joints.leftKnee, joints.leftAnkle], legWidth);
                drawCapsule([joints.rightHip, joints.rightKnee, joints.rightAnkle], legWidth);

                const torso = shape.torso.map(px);
                ctx.beginPath();
                torso.forEach((point, index) => {
                    if (index === 0) ctx.moveTo(point.x, point.y);
                    else ctx.lineTo(point.x, point.y);
                });
                ctx.closePath();
                ctx.fillStyle = 'rgba(245, 158, 11, 0.10)';
                ctx.strokeStyle = '#f59e0b';
                ctx.lineWidth = Math.max(2, width * 0.006);
                ctx.fill();
                ctx.stroke();
                ctx.restore();
            },
            drawPoseGuideOverlay(ctx, width, height, bodyBox) {
                const centerX = bodyBox.x + bodyBox.w / 2;
                const headRadius = Math.max(8, bodyBox.w * 0.16);
                const headY = bodyBox.y + headRadius * 1.2;
                const shoulderY = bodyBox.y + bodyBox.h * 0.24;
                const hipY = bodyBox.y + bodyBox.h * 0.55;
                const kneeY = bodyBox.y + bodyBox.h * 0.76;
                const ankleY = bodyBox.y + bodyBox.h * 0.96;
                const shoulderHalf = bodyBox.w * 0.28;
                const hipHalf = bodyBox.w * 0.2;

                ctx.save();
                ctx.strokeStyle = '#f59e0b';
                ctx.fillStyle = 'rgba(245, 158, 11, 0.06)';
                ctx.lineWidth = Math.max(3, width * 0.006);
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';

                ctx.beginPath();
                ctx.arc(centerX, headY, headRadius, 0, Math.PI * 2);
                ctx.fill();
                ctx.stroke();

                const segments = [
                    [centerX, headY + headRadius, centerX, hipY],
                    [centerX - shoulderHalf, shoulderY, centerX + shoulderHalf, shoulderY],
                    [centerX - shoulderHalf, shoulderY, centerX - hipHalf * 1.2, hipY],
                    [centerX + shoulderHalf, shoulderY, centerX + hipHalf * 1.2, hipY],
                    [centerX - hipHalf, hipY, centerX + hipHalf, hipY],
                    [centerX - hipHalf, hipY, centerX - hipHalf * 0.9, kneeY],
                    [centerX - hipHalf * 0.9, kneeY, centerX - hipHalf * 0.75, ankleY],
                    [centerX + hipHalf, hipY, centerX + hipHalf * 0.9, kneeY],
                    [centerX + hipHalf * 0.9, kneeY, centerX + hipHalf * 0.75, ankleY],
                ];
                segments.forEach(([x1, y1, x2, y2]) => {
                    ctx.beginPath();
                    ctx.moveTo(x1, y1);
                    ctx.lineTo(x2, y2);
                    ctx.stroke();
                });
                ctx.restore();
            },
            drawManualReferenceBox(ctx, box, color, label) {
                ctx.save();
                ctx.lineWidth = Math.max(3, box.image_width * 0.007);
                ctx.strokeStyle = color;
                ctx.fillStyle = 'rgba(239, 68, 68, 0.08)';
                ctx.fillRect(box.x, box.y, box.w, box.h);
                ctx.setLineDash([]);
                ctx.strokeRect(box.x, box.y, box.w, box.h);
                ctx.fillStyle = color;
                ctx.fillRect(box.x, Math.max(0, box.y - 24), 92, 24);
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 13px sans-serif';
                ctx.fillText(label, box.x + 8, Math.max(16, box.y - 8));

                const handleSize = Math.max(10, box.image_width * 0.018);
                const half = handleSize / 2;
                const handles = [
                    [box.x, box.y],
                    [box.x + box.w, box.y],
                    [box.x, box.y + box.h],
                    [box.x + box.w, box.y + box.h],
                ];
                ctx.fillStyle = '#ffffff';
                ctx.strokeStyle = color;
                ctx.lineWidth = 2;
                handles.forEach(([x, y]) => {
                    ctx.beginPath();
                    ctx.rect(x - half, y - half, handleSize, handleSize);
                    ctx.fill();
                    ctx.stroke();
                });

                ctx.fillStyle = color;
                ctx.font = 'bold 11px sans-serif';
                ctx.fillText('geser kotak, tarik sudut untuk resize', box.x + 6, Math.min(box.image_height - 8, box.y + box.h + 18));
                ctx.restore();
            },
        };
    }
</script>
@endpush
