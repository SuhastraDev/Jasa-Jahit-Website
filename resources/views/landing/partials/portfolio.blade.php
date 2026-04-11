@php
    $dummyPortfolios = [
        ['title' => 'Kemeja Formal Pria', 'tag' => 'Custom', 'gradient' => 'from-blue-500 to-indigo-600', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'desc' => 'Kemeja formal pria bahan katun premium, lengan panjang dengan cutting slim fit custom sesuai ukuran badan.'],
        ['title' => 'Gaun Pesta Elegan', 'tag' => 'Desain', 'gradient' => 'from-pink-500 to-rose-600', 'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', 'desc' => 'Gaun pesta malam dengan detail renda halus, bahan satin premium, desain eksklusif sesuai referensi pelanggan.'],
        ['title' => 'Batik Modern', 'tag' => 'Custom', 'gradient' => 'from-amber-500 to-orange-600', 'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z', 'desc' => 'Kemeja batik motif kontemporer dengan perpaduan warna modern. Cocok untuk acara semi-formal dan casual.'],
        ['title' => 'Celana Chino Slim', 'tag' => 'Permak', 'gradient' => 'from-teal-500 to-cyan-600', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'desc' => 'Celana chino dipermak menjadi slim fit sempurna. Pekerjaan rapi dengan jahitan tersembunyi.'],
        ['title' => 'Blazer Formal Wanita', 'tag' => 'Custom', 'gradient' => 'from-violet-500 to-purple-600', 'icon' => 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4', 'desc' => 'Blazer formal wanita dengan bahan wool premium. Tailored fit dengan detail lapel dan kancing yang elegan.'],
        ['title' => 'Dress Kasual Motif', 'tag' => 'Desain', 'gradient' => 'from-emerald-500 to-green-600', 'icon' => 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z', 'desc' => 'Dress kasual wanita motif floral pastel. Desain A-line yang nyaman dipakai untuk berbagai kesempatan.'],
        ['title' => 'Kemeja Batik Kontemporer', 'tag' => 'Custom', 'gradient' => 'from-red-500 to-rose-600', 'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01', 'desc' => 'Kemeja batik pria dengan motif geometris modern. Perpaduan tradisional dan kontemporer yang sempurna.'],
        ['title' => 'Rok Midi Floral', 'tag' => 'Desain', 'gradient' => 'from-sky-500 to-blue-600', 'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'desc' => 'Rok midi motif floral cerah, bahan chiffon ringan dengan potongan A-line yang feminin dan elegan.'],
    ];
@endphp

<div id="portfolio" class="py-20 bg-gray-50" x-data="{
    modalOpen: false,
    modalTitle: '',
    modalDesc: '',
    modalTag: '',
    modalGradient: '',
    modalIcon: '',
    modalImage: '',
    modalIsReal: false,
    openDummy(title, desc, tag, gradient, icon) {
        this.modalTitle = title;
        this.modalDesc = desc;
        this.modalTag = tag;
        this.modalGradient = gradient;
        this.modalIcon = icon;
        this.modalIsReal = false;
        this.modalImage = '';
        this.modalOpen = true;
    },
    openReal(title, imageUrl) {
        this.modalTitle = title;
        this.modalDesc = '';
        this.modalIsReal = true;
        this.modalImage = imageUrl;
        this.modalTag = '';
        this.modalOpen = true;
    }
}">

    <!-- Modal Lightbox -->
    <div x-show="modalOpen" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
         @click.self="modalOpen = false"
         @keydown.escape.window="modalOpen = false">

        <div x-show="modalOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-2xl shadow-2xl overflow-hidden max-w-lg w-full max-h-[90vh] flex flex-col">

            <!-- Modal Image / Gradient preview -->
            <div class="relative">
                <!-- Real image -->
                <template x-if="modalIsReal">
                    <img :src="modalImage" :alt="modalTitle" class="w-full h-64 object-cover">
                </template>

                <!-- Dummy gradient preview -->
                <template x-if="!modalIsReal">
                    <div class="w-full h-64 flex items-center justify-center relative overflow-hidden"
                         :class="'bg-gradient-to-br ' + modalGradient">
                        <div class="absolute inset-0 opacity-[0.08]" style="background-image: repeating-linear-gradient(0deg, #fff 0, #fff 1px, transparent 0, transparent 50%), repeating-linear-gradient(90deg, #fff 0, #fff 1px, transparent 0, transparent 50%); background-size: 20px 20px;"></div>
                        <svg class="w-24 h-24 text-white/40 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" :d="modalIcon"/>
                        </svg>
                    </div>
                </template>

                <!-- Close button -->
                <button @click="modalOpen = false"
                        class="absolute top-3 right-3 w-8 h-8 bg-black/40 hover:bg-black/60 text-white rounded-full flex items-center justify-center transition-colors backdrop-blur-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <!-- Tag badge -->
                <template x-if="modalTag">
                    <div class="absolute bottom-3 left-3">
                        <span class="bg-white/20 backdrop-blur-sm text-white text-xs font-semibold px-2.5 py-1 rounded-full border border-white/30" x-text="modalTag"></span>
                    </div>
                </template>
            </div>

            <!-- Modal Content -->
            <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-2" x-text="modalTitle"></h3>
                <template x-if="modalDesc">
                    <p class="text-gray-500 text-sm leading-relaxed" x-text="modalDesc"></p>
                </template>
                <div class="mt-5 flex items-center gap-3">
                    <a href="{{ route('register') }}"
                       class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white py-2.5 px-4 rounded-xl font-semibold text-sm transition-colors">
                        Pesan Sekarang
                    </a>
                    <button @click="modalOpen = false"
                            class="px-4 py-2.5 border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl text-sm font-medium transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <span class="inline-block text-blue-600 font-semibold text-sm uppercase tracking-widest mb-3">Portfolio</span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-4">Karya Terbaik Kami</h2>
            <p class="text-gray-500 max-w-xl mx-auto">Setiap pakaian adalah hasil dari ketelitian, kreativitas, dan dedikasi penjahit kami. Klik untuk melihat detail.</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
            @forelse($portfolios as $portfolio)
                <div class="group cursor-pointer"
                     @click="openReal('{{ addslashes($portfolio->title) }}', '{{ asset('storage/' . preg_replace('#^public/#', '', $portfolio->image_path)) }}')">
                    <div class="rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100">
                        <div class="relative h-52 overflow-hidden">
                            <img src="{{ asset('storage/' . preg_replace('#^public/#', '', $portfolio->image_path)) }}" alt="{{ $portfolio->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-3">
                                <span class="text-white text-xs font-medium">Lihat Detail →</span>
                            </div>
                        </div>
                        <div class="bg-white p-4">
                            <h4 class="font-semibold text-gray-900 text-sm truncate">{{ $portfolio->title }}</h4>
                        </div>
                    </div>
                </div>
            @empty
                @foreach($dummyPortfolios as $dummy)
                <div class="group cursor-pointer"
                     @click="openDummy('{{ $dummy['title'] }}', '{{ $dummy['desc'] }}', '{{ $dummy['tag'] }}', '{{ $dummy['gradient'] }}', '{{ $dummy['icon'] }}')">
                    <div class="rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100">
                        <div class="relative h-52 bg-gradient-to-br {{ $dummy['gradient'] }} flex items-center justify-center overflow-hidden">
                            <div class="absolute inset-0 opacity-[0.08]" style="background-image: repeating-linear-gradient(0deg, #fff 0, #fff 1px, transparent 0, transparent 50%), repeating-linear-gradient(90deg, #fff 0, #fff 1px, transparent 0, transparent 50%); background-size: 20px 20px;"></div>
                            <svg class="w-16 h-16 text-white/50 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $dummy['icon'] }}"/>
                            </svg>
                            <div class="absolute top-3 left-3">
                                <span class="bg-white/20 backdrop-blur-sm text-white text-xs font-semibold px-2.5 py-1 rounded-full border border-white/30">{{ $dummy['tag'] }}</span>
                            </div>
                            <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end justify-center pb-3">
                                <span class="text-white text-xs font-medium bg-black/30 px-3 py-1.5 rounded-full backdrop-blur-sm">Lihat Detail →</span>
                            </div>
                        </div>
                        <div class="bg-white p-4">
                            <h4 class="font-semibold text-gray-900 text-sm">{{ $dummy['title'] }}</h4>
                            <p class="text-gray-400 text-xs mt-0.5">Contoh karya</p>
                        </div>
                    </div>
                </div>
                @endforeach
            @endforelse
        </div>
          

            <div class="text-center mt-14">
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-blue-600 text-white px-8 py-4 rounded-xl font-bold hover:bg-blue-700 transition-colors shadow-sm hover:shadow-md text-base">
                Pesan Pakaian Kustom Anda
            </a>
        </div>
    </div>
</div>
