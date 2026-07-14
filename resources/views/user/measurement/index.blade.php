@extends('layouts.user')
@section('page-title', 'Ukur Badan')
@section('content')

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-8" x-data="measurementCapture()" x-init="init()">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Ukur Badan Multi-view</h1>
        <p class="text-gray-500 text-sm mt-1">Ambil foto langsung dari kamera atau upload foto depan, samping, dan belakang dengan papan patokan ukuran yang berdiri sendiri.</p>
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
                        <p class="text-sm text-gray-500 mt-1">Papan patokan ukuran tidak boleh dipegang oleh user. Tempelkan di dinding, papan tegak, tripod kecil, hanger, atau tiang penyangga.</p>
                    </div>
                    <span class="text-xs font-semibold px-3 py-1.5 rounded-full bg-blue-50 text-blue-700">3 foto wajib</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @foreach([
                        ['Depan', 'Badan menghadap kamera. Papan patokan tegak di samping tubuh dan tidak menutup siluet.'],
                        ['Samping', 'User menghadap kiri/kanan. Papan patokan tetap terlihat kamera, berdiri sejajar jarak tubuh.'],
                        ['Belakang', 'Punggung menghadap kamera. Papan patokan tetap di samping tubuh dan terlihat penuh.'],
                    ] as [$title, $desc])
                    <div class="border border-gray-100 rounded-xl p-4">
                        <p class="font-semibold text-gray-900 text-sm">{{ $title }}</p>
                        <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $desc }}</p>
                    </div>
                    @endforeach
                </div>

                <div class="mt-4 bg-amber-50 border border-amber-100 rounded-xl p-4">
                    <p class="text-sm font-semibold text-amber-900">Aturan wajib</p>
                    <p class="text-xs text-amber-800 mt-1 leading-relaxed">Kamera sejajar dada/pinggang, tubuh penuh kepala sampai kaki, pakaian fit, tangan rileks sedikit menjauh dari badan, pencahayaan cukup, dan papan patokan berada pada bidang yang sama dengan tubuh.</p>
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
                                <text x="309" y="205" text-anchor="middle" fill="#e0f2fe" font-size="10" font-weight="700">Papan patokan</text>
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

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <button type="button" @click="startCamera()" class="px-4 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-bold hover:bg-slate-800">
                                Aktifkan Kamera
                            </button>
                            <button type="button" @click="capturePose(activePose)" :disabled="!cameraReady"
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
                                    <text x="203" y="176" text-anchor="middle" fill="#0369a1" font-size="9" font-weight="700">Papan</text>

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

                <form action="{{ route('user.measurement.analyze') }}" method="POST" enctype="multipart/form-data" class="space-y-5" @submit="startAnalysis()">
                    @csrf

                    <div>
                        <label for="ref_object" class="block text-sm font-semibold text-gray-700 mb-1.5">Papan Patokan Ukuran <span class="text-red-500">*</span></label>
                        <select name="ref_object" id="ref_object" x-model="refObject" class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="aruco_a4">Papan ArUco A4 (direkomendasikan)</option>
                            <option value="checkerboard_a4">Papan kotak-kotak A4</option>
                            <option value="a4">Kertas A4 polos (akurasi lebih rendah)</option>
                            <option value="custom">Custom (ukuran sendiri)</option>
                        </select>
                    </div>

                    <div x-show="refObject === 'custom'" x-transition class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="ref_width_cm" class="block text-xs font-semibold text-gray-600 mb-1">Lebar papan/kertas (cm)</label>
                            <input type="number" step="0.1" name="ref_width_cm" id="ref_width_cm" value="{{ old('ref_width_cm') }}" class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Contoh: 21">
                        </div>
                        <div>
                            <label for="ref_height_cm" class="block text-xs font-semibold text-gray-600 mb-1">Tinggi papan/kertas (cm)</label>
                            <input type="number" step="0.1" name="ref_height_cm" id="ref_height_cm" value="{{ old('ref_height_cm') }}" class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Contoh: 29.7">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach([
                            ['front_photo', 'front', 'Foto Depan', 'User menghadap kamera'],
                            ['side_photo', 'side', 'Foto Samping', 'User menghadap kiri/kanan'],
                            ['back_photo', 'back', 'Foto Belakang', 'Punggung menghadap kamera'],
                        ] as [$name, $key, $label, $hint])
                        <div class="border border-gray-100 rounded-xl p-4">
                            <label for="{{ $name }}" class="block text-sm font-semibold text-gray-700 mb-1.5">{{ $label }} <span class="text-red-500">*</span></label>
                            <p class="text-xs text-gray-400 mb-3">{{ $hint }}</p>
                            <input type="file" name="{{ $name }}" id="{{ $name }}" x-ref="{{ $key }}Input" accept="image/*" required
                                   @change="handleUpload($event, '{{ $key }}')"
                                   class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                            <div x-show="previews.{{ $key }}" class="mt-3">
                                <img :src="previews.{{ $key }}" class="h-48 w-full rounded-lg border border-gray-200 object-contain bg-gray-50" alt="Preview {{ $label }}">
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <button type="submit" :disabled="isAnalyzing" class="w-full px-6 py-3 bg-blue-600 text-white rounded-xl font-bold text-sm hover:bg-blue-700 transition-colors shadow-sm disabled:opacity-70 disabled:cursor-wait">
                        <span x-show="!isAnalyzing">Validasi dan Hitung Ukuran</span>
                        <span x-show="isAnalyzing" x-cloak>Memproses foto...</span>
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
                                @foreach([['Dada',$m->chest],['Pinggang',$m->waist],['Pinggul',$m->hips],['Bahu',$m->shoulder_width],['Lengan',$m->arm_length],['Tinggi',$m->height]] as [$lbl,$val])
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

    <div x-show="isAnalyzing" x-cloak class="fixed inset-0 z-50 bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-white/70 overflow-hidden">
            <div class="bg-slate-900 px-6 py-5 text-white">
                <p class="text-sm font-semibold text-sky-200">Analisis ukuran sedang berjalan</p>
                <h3 class="text-xl font-black mt-1">Mengecek foto dan menghitung ukuran tubuh</h3>
                <p class="text-sm text-slate-300 mt-2">Jangan tutup halaman sampai proses selesai.</p>
            </div>
            <div class="p-6">
                <div class="flex items-center gap-4 mb-5">
                    <div class="relative h-16 w-16 flex-shrink-0">
                        <div class="absolute inset-0 rounded-full border-4 border-sky-100"></div>
                        <div class="absolute inset-0 rounded-full border-4 border-sky-500 border-t-transparent animate-spin"></div>
                        <div class="absolute inset-4 rounded-full bg-sky-50"></div>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">Foto depan, samping, dan belakang sedang diproses.</p>
                        <p class="text-xs text-gray-500 mt-1">Sistem mengecek papan patokan, membaca pose tubuh, lalu menghitung ukuran dalam sentimeter.</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <template x-for="(step, index) in processSteps" :key="step">
                        <div class="flex items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 p-3">
                            <span class="h-7 w-7 rounded-full bg-blue-600 text-white text-xs font-black flex items-center justify-center" x-text="index + 1"></span>
                            <span class="text-sm font-semibold text-gray-700" x-text="step"></span>
                        </div>
                    </template>
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
            refObject: '{{ old('ref_object', 'aruco_a4') }}',
            activePose: 'front',
            cameraReady: false,
            cameraError: '',
            cameraFacing: 'environment',
            stream: null,
            isAnalyzing: false,
            previews: { front: null, side: null, back: null },
            processSteps: [
                'Mengecek kualitas tiga foto',
                'Mendeteksi papan patokan ukuran',
                'Membaca pose dan siluet tubuh',
                'Menghitung ukuran baju dan celana',
            ],
            poseList: [
                { key: 'front', label: 'Foto Depan', hint: 'Badan menghadap kamera, papan patokan di sisi tubuh.' },
                { key: 'side', label: 'Foto Samping', hint: 'Badan menghadap kiri/kanan, papan patokan tetap terlihat.' },
                { key: 'back', label: 'Foto Belakang', hint: 'Punggung menghadap kamera, papan patokan di sisi tubuh.' },
            ],
            get activePoseLabel() {
                return this.poseList.find((pose) => pose.key === this.activePose)?.label || 'Foto Depan';
            },
            init() {
                window.addEventListener('beforeunload', () => this.stopCamera());
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
            startAnalysis() {
                this.isAnalyzing = true;
                this.stopCamera();
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
                    } catch (fallbackError) {
                        this.cameraReady = false;
                        this.cameraError = this.cameraFacing === 'environment'
                            ? 'Kamera belakang tidak tersedia atau akses kamera ditolak.'
                            : 'Kamera depan tidak tersedia atau akses kamera ditolak.';
                    }
                }
            },
            stopCamera() {
                if (this.stream) {
                    this.stream.getTracks().forEach((track) => track.stop());
                    this.stream = null;
                }
                this.cameraReady = false;
            },
            async capturePose(pose) {
                if (!this.cameraReady || !this.$refs.video.videoWidth) return;

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
                    return;
                }

                if (this.previews[pose]) URL.revokeObjectURL(this.previews[pose]);
                this.previews[pose] = URL.createObjectURL(file);
            },
        };
    }
</script>
@endpush
