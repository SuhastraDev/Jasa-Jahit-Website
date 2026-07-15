<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ZRINTTAILOR') }} — Platform Jasa Jahit Online</title>
    @include('partials.seo-meta')

    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="shortcut icon" href="/favicon.svg">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:300,400,500,600,700,800,900&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased text-gray-900 bg-white">

    <!-- Navbar -->
    <header class="w-full bg-white/95 backdrop-blur-sm border-b border-gray-100 sticky top-0 z-50" x-data="{ open: false }">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">

                <!-- Logo -->
                <a href="/" class="flex items-center gap-2.5 group">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center shadow-sm group-hover:bg-blue-700 transition-colors">
                        <svg class="w-4.5 h-4.5 text-white" style="width:18px;height:18px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-gray-900 tracking-tight">ZRINT<span class="text-blue-600">TAILOR</span></span>
                </a>

                <!-- Desktop Nav Links -->
                <div class="hidden md:flex items-center space-x-1">
                    <a href="{{ route('landing') }}#services" class="text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg font-medium text-sm transition-colors">Layanan</a>
                    <a href="{{ route('catalogs.public') }}" class="text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg font-medium text-sm transition-colors">Katalog</a>
                    <a href="{{ route('landing') }}#portfolio" class="text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg font-medium text-sm transition-colors">Portfolio</a>
                    <a href="{{ route('landing') }}#contact" class="text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg font-medium text-sm transition-colors">Kontak</a>
                </div>

                <!-- Desktop Auth Buttons -->
                <div class="hidden md:flex items-center gap-2">
                    @auth
                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" class="text-gray-700 hover:text-blue-600 font-medium text-sm px-4 py-2 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">Dashboard Admin</a>
                        @else
                            <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-blue-600 font-medium text-sm px-4 py-2 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">Dashboard</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="text-gray-600 hover:text-blue-600 font-medium text-sm px-4 py-2 transition-colors">Masuk</a>
                        <a href="{{ route('register') }}" class="bg-blue-600 text-white text-sm px-5 py-2.5 rounded-lg font-semibold hover:bg-blue-700 transition-colors shadow-sm">Daftar Gratis</a>
                    @endauth
                </div>

                <!-- Mobile Hamburger -->
                <button @click="open = !open" class="md:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                    <svg x-show="!open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="open" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Mobile Menu -->
            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="md:hidden border-t border-gray-100 py-3">
                <div class="space-y-1">
                    <a href="{{ route('landing') }}#services" @click="open=false" class="flex items-center text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors">Layanan</a>
                    <a href="{{ route('catalogs.public') }}" @click="open=false" class="flex items-center text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors">Katalog</a>
                    <a href="{{ route('landing') }}#portfolio" @click="open=false" class="flex items-center text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors">Portfolio</a>
                    <a href="{{ route('landing') }}#contact" @click="open=false" class="flex items-center text-gray-600 hover:text-blue-600 hover:bg-blue-50 px-4 py-2.5 rounded-lg font-medium text-sm transition-colors">Kontak</a>
                </div>
                <div class="flex gap-3 mt-4 pt-3 border-t border-gray-100">
                    @guest
                        <a href="{{ route('login') }}" class="flex-1 text-center border border-gray-200 text-gray-700 px-4 py-2.5 rounded-lg font-medium text-sm hover:border-blue-300 transition-colors">Masuk</a>
                        <a href="{{ route('register') }}" class="flex-1 text-center bg-blue-600 text-white px-4 py-2.5 rounded-lg font-semibold text-sm hover:bg-blue-700 transition-colors">Daftar</a>
                    @endguest
                    @auth
                        <a href="{{ auth()->user()->role === 'admin' ? route('admin.dashboard') : route('dashboard') }}" class="flex-1 text-center bg-blue-600 text-white px-4 py-2.5 rounded-lg font-semibold text-sm">Dashboard</a>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
            <div class="grid md:grid-cols-4 gap-10 mb-10">
                <!-- Brand -->
                <div class="md:col-span-2">
                    <div class="flex items-center gap-2.5 mb-4">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <svg style="width:18px;height:18px" class="text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
                        </div>
                        <span class="text-white font-bold text-xl tracking-tight">ZRINT<span class="text-blue-400">TAILOR</span></span>
                    </div>
                    <p class="text-sm leading-relaxed max-w-xs">Layanan jahit profesional yang menggabungkan keahlian penjahit berpengalaman dengan teknologi Computer Vision untuk ukuran pakaian yang selalu pas.</p>
                </div>

                <!-- Nav -->
                <div>
                    <h4 class="text-white font-semibold text-sm mb-4 uppercase tracking-wider">Navigasi</h4>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="{{ route('landing') }}#services" class="hover:text-white transition-colors">Layanan</a></li>
                        <li><a href="{{ route('catalogs.public') }}" class="hover:text-white transition-colors">Katalog Desain</a></li>
                        <li><a href="{{ route('landing') }}#portfolio" class="hover:text-white transition-colors">Portfolio</a></li>
                        <li><a href="{{ route('landing') }}#contact" class="hover:text-white transition-colors">Kontak</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="text-white font-semibold text-sm mb-4 uppercase tracking-wider">Kontak</h4>
                    <ul class="space-y-3 text-sm">
                        <li>
                            <a href="https://wa.me/6281234567890" target="_blank" class="flex items-center gap-2.5 hover:text-white transition-colors">
                                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                +62 812 3456 7890
                            </a>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Jl. Tailor Indah No. 1
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 flex flex-col sm:flex-row justify-between items-center gap-3 text-sm">
                <p>&copy; {{ date('Y') }} ZRINTTAILOR. Semua Hak Cipta Dilindungi.</p>
                <div class="flex items-center gap-2 text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span>Pembayaran 100% Aman & Terverifikasi</span>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>
