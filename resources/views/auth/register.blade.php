<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Daftar — {{ config('app.name', 'ZRINTTAILOR') }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <meta name="theme-color" content="#1e3a5f">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white">
<div class="min-h-screen flex">

    <!-- ── Left Panel: Brand ── -->
    <div class="hidden lg:flex lg:w-[48%] bg-gradient-to-br from-slate-900 via-indigo-900 to-blue-900 relative overflow-hidden flex-col justify-between p-12 xl:p-16">
        <!-- Blobs -->
        <div class="absolute top-0 left-0 w-72 h-72 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 right-0 w-64 h-64 bg-blue-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Logo -->
        <div class="relative">
            <a href="/" class="inline-flex items-center gap-3">
                <div class="w-10 h-10 bg-white/15 border border-white/20 rounded-xl flex items-center justify-center">
                    <svg style="width:20px;height:20px" class="text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/>
                    </svg>
                </div>
                <span class="text-2xl font-bold text-white tracking-tight">ZRNT<span class="text-blue-300">TAILOR</span></span>
            </a>
        </div>

        <!-- Main Content -->
        <div class="relative">
            <div class="inline-flex items-center gap-2 bg-white/10 border border-white/20 text-blue-200 text-xs px-3 py-1.5 rounded-full mb-6">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                Daftar Gratis — Tidak Ada Biaya
            </div>
            <h2 class="text-4xl xl:text-5xl font-extrabold text-white leading-tight mb-4">
                Mulai Perjalanan<br>
                <span class="text-blue-300">Jahit Terbaik Anda.</span>
            </h2>
            <p class="text-blue-100/80 text-lg leading-relaxed mb-10">
                Buat akun dan dapatkan akses ke semua fitur — dari pengukuran AI hingga katalog desain eksklusif.
            </p>

            <!-- Feature Steps -->
            <div class="space-y-5">
                <div class="flex items-start gap-4">
                    <div class="w-9 h-9 bg-blue-500/30 border border-blue-400/30 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="text-blue-200 text-sm font-bold">1</span>
                    </div>
                    <div>
                        <div class="text-white font-semibold text-sm">Buat akun gratis</div>
                        <div class="text-blue-200/70 text-xs mt-0.5">Daftar dengan email dan nomor WhatsApp aktif</div>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div class="w-9 h-9 bg-purple-500/30 border border-purple-400/30 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="text-purple-200 text-sm font-bold">2</span>
                    </div>
                    <div>
                        <div class="text-white font-semibold text-sm">Upload foto untuk ukuran badan</div>
                        <div class="text-blue-200/70 text-xs mt-0.5">CV AI kami estimasi ukuran secara otomatis dan presisi</div>
                    </div>
                </div>
                <div class="flex items-start gap-4">
                    <div class="w-9 h-9 bg-green-500/30 border border-green-400/30 rounded-xl flex items-center justify-center flex-shrink-0">
                        <span class="text-green-200 text-sm font-bold">3</span>
                    </div>
                    <div>
                        <div class="text-white font-semibold text-sm">Pilih layanan & pesan</div>
                        <div class="text-blue-200/70 text-xs mt-0.5">Permak, Desain referensi, atau Custom sepenuhnya</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trust badges -->
        <div class="relative grid grid-cols-3 gap-3">
            <div class="bg-white/10 border border-white/15 rounded-xl p-3 text-center">
                <div class="text-2xl font-extrabold text-white">200+</div>
                <div class="text-blue-300 text-xs mt-0.5">Pelanggan Puas</div>
            </div>
            <div class="bg-white/10 border border-white/15 rounded-xl p-3 text-center">
                <div class="text-2xl font-extrabold text-white">4.9</div>
                <div class="text-blue-300 text-xs mt-0.5">Rating</div>
            </div>
            <div class="bg-white/10 border border-white/15 rounded-xl p-3 text-center">
                <div class="text-2xl font-extrabold text-white">3+</div>
                <div class="text-blue-300 text-xs mt-0.5">Tahun Aktif</div>
            </div>
        </div>
    </div>

    <!-- ── Right Panel: Form ── -->
    <div class="flex-1 flex flex-col justify-center px-6 py-10 sm:px-10 lg:px-14 xl:px-20 bg-gray-50 lg:bg-white overflow-y-auto">

        <!-- Mobile Logo -->
        <div class="lg:hidden mb-8">
            <a href="/" class="inline-flex items-center gap-2">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                    <svg style="width:18px;height:18px" class="text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
                </div>
                <span class="text-xl font-bold text-gray-900 tracking-tight">ZRNT<span class="text-blue-600">TAILOR</span></span>
            </a>
        </div>

        <div class="max-w-md w-full mx-auto">
            <div class="mb-8">
                <h1 class="text-3xl font-extrabold text-gray-900 mb-2 tracking-tight">Buat Akun Baru</h1>
                <p class="text-gray-500">Gratis selamanya. Mulai jahit dengan presisi hari ini.</p>
            </div>

            <!-- Errors -->
            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <ul class="space-y-0.5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Lengkap</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}"
                        class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all @error('name') border-red-400 bg-red-50 @enderror"
                        placeholder="Nama lengkap Anda" required autofocus autocomplete="name">
                    @error('name')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Alamat Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                        class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all @error('email') border-red-400 bg-red-50 @enderror"
                        placeholder="nama@email.com" required autocomplete="username">
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Nomor WhatsApp
                        <span class="text-gray-400 font-normal text-xs ml-1">— untuk notifikasi pesanan</span>
                    </label>
                    <input id="phone" type="text" name="phone" value="{{ old('phone') }}"
                        class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all @error('phone') border-red-400 bg-red-50 @enderror"
                        placeholder="08123456789" required autocomplete="tel">
                    @error('phone')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
                    <div class="relative" x-data="{ show: false }">
                        <input id="password" :type="show ? 'text' : 'password'" name="password"
                            class="w-full px-4 py-3 pr-11 bg-white border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all @error('password') border-red-400 bg-red-50 @enderror"
                            placeholder="Minimal 8 karakter" required autocomplete="new-password">
                        <button type="button" @click="show = !show"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600 transition-colors">
                            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-1.5">Konfirmasi Password</label>
                    <div class="relative" x-data="{ show: false }">
                        <input id="password_confirmation" :type="show ? 'text' : 'password'" name="password_confirmation"
                            class="w-full px-4 py-3 pr-11 bg-white border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all @error('password_confirmation') border-red-400 bg-red-50 @enderror"
                            placeholder="Ulangi password Anda" required autocomplete="new-password">
                        <button type="button" @click="show = !show"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600 transition-colors">
                            <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                    @error('password_confirmation')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Terms note -->
                <p class="text-xs text-gray-400 leading-relaxed">
                    Dengan mendaftar, Anda menyetujui <span class="text-gray-600 font-medium">syarat & ketentuan</span> layanan ZRINTTAILOR.
                </p>

                <!-- Submit -->
                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold py-3.5 px-6 rounded-xl transition-colors duration-200 shadow-sm hover:shadow-md text-base">
                    Buat Akun Gratis
                </button>
            </form>

            {{-- Divider --}}
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
                <div class="relative flex justify-center text-xs">
                    <span class="bg-gray-50/30 px-3 text-gray-400 font-medium">atau daftar dengan</span>
                </div>
            </div>

            {{-- Google Register --}}
            <a href="{{ route('auth.google') }}"
               class="w-full flex items-center justify-center gap-3 px-4 py-3 bg-white border border-gray-200 rounded-xl text-gray-700 text-sm font-semibold hover:bg-gray-50 hover:border-gray-300 transition-all shadow-sm">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Daftar dengan Google
            </a>

            <div class="mt-6 pt-6 border-t border-gray-100 text-center">
                <p class="text-sm text-gray-500">
                    Sudah punya akun?
                    <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-700 font-semibold ml-1">Masuk di sini</a>
                </p>
            </div>

            <p class="mt-4 text-center">
                <a href="/" class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Kembali ke Beranda
                </a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
