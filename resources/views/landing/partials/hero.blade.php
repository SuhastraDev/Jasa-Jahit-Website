<div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 text-white">
    <!-- Decorative blobs -->
    <div class="absolute top-0 right-0 w-96 h-96 bg-blue-500/15 rounded-full blur-3xl -translate-y-1/3 translate-x-1/3 pointer-events-none"></div>
    <div class="absolute bottom-0 left-0 w-80 h-80 bg-indigo-500/15 rounded-full blur-3xl translate-y-1/3 -translate-x-1/3 pointer-events-none"></div>
    <div class="absolute top-1/2 left-1/2 w-64 h-64 bg-blue-400/10 rounded-full blur-2xl -translate-x-1/2 -translate-y-1/2 pointer-events-none"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 text-sm px-4 py-2 rounded-full mb-8 text-blue-100">
            <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse flex-shrink-0"></span>
            Didukung Teknologi Computer Vision AI
        </div>

        <div class="max-w-3xl">
            <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold leading-tight mb-6 tracking-tight">
                Pakaian <span class="text-blue-300">Pas di Badan</span>,<br class="hidden sm:block">
                Tanpa Repot
            </h1>
            <p class="text-lg sm:text-xl text-blue-100/90 mb-10 leading-relaxed max-w-2xl">
                Layanan jahit profesional dengan teknologi pengukuran otomatis. Cukup upload foto sistem kami mengestimasi ukuran badan Anda secara presisi dan langsung diproses penjahit berpengalaman.
            </p>

            <div class="flex flex-col sm:flex-row gap-4">
                <a href="{{ route('register') }}"
                    class="inline-flex items-center justify-center gap-2 bg-white text-blue-700 px-8 py-4 rounded-xl font-bold shadow-lg hover:shadow-xl hover:bg-blue-50 transition-all duration-200 text-base">
                    Mulai Pesan Sekarang
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
                <a href="{{ route('catalogs.public') }}"
                    class="inline-flex items-center justify-center gap-2 bg-white/10 backdrop-blur-sm border border-white/30 text-white px-8 py-4 rounded-xl font-semibold hover:bg-white/20 transition-all duration-200 text-base">
                    Lihat Katalog Desain
                </a>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="mt-16 pt-10 border-t border-white/15 grid grid-cols-3 gap-6 max-w-lg">
            <div>
                <div class="text-3xl sm:text-4xl font-extrabold text-white">200+</div>
                <div class="text-blue-300 text-sm mt-1">Pelanggan Puas</div>
            </div>
            <div>
                <div class="text-3xl sm:text-4xl font-extrabold text-white flex items-center gap-1">
                    4.9
                    <svg class="w-6 h-6 text-yellow-400 fill-yellow-400 flex-shrink-0" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                </div>
                <div class="text-blue-300 text-sm mt-1">Rating Rata-rata</div>
            </div>
            <div>
                <div class="text-3xl sm:text-4xl font-extrabold text-white">3+</div>
                <div class="text-blue-300 text-sm mt-1">Tahun Berpengalaman</div>
            </div>
        </div>
    </div>
</div>
