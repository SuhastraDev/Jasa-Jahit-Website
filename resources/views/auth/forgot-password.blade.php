<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Password — {{ config('app.name', 'ZRINTTAILOR') }}</title>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white">
<div class="min-h-screen flex">

    <!-- Left Panel -->
    <div class="hidden lg:flex lg:w-[48%] bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 relative overflow-hidden flex-col justify-between p-12 xl:p-16">
        <div class="absolute top-0 right-0 w-72 h-72 bg-blue-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div>
            <a href="/" class="inline-flex items-center gap-3">
                <div class="w-10 h-10 bg-white/15 border border-white/20 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
                </div>
                <span class="text-2xl font-bold text-white tracking-tight">ZRNT<span class="text-blue-300">TAILOR</span></span>
            </a>
        </div>
        <div>
            <h2 class="text-4xl xl:text-5xl font-extrabold text-white leading-tight mb-4">Lupa Password?<br><span class="text-blue-300">Tenang, Kami Bantu.</span></h2>
            <p class="text-blue-100/80 text-lg leading-relaxed">Masukkan email dan kami kirimkan tautan untuk mengatur ulang password Anda.</p>
        </div>
        <p class="text-blue-200/50 text-xs">© {{ date('Y') }} ZRINTTAILOR. All rights reserved.</p>
    </div>

    <!-- Right Panel -->
    <div class="flex-1 flex items-center justify-center px-6 sm:px-12 py-12 bg-gray-50/30">
        <div class="w-full max-w-md">
            <div class="lg:hidden mb-8 flex items-center gap-3">
                <div class="w-9 h-9 bg-slate-900 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
                </div>
                <span class="text-xl font-bold text-slate-900">ZRNT<span class="text-blue-600">TAILOR</span></span>
            </div>

            <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center mb-6">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 mb-2">Lupa Password</h1>
            <p class="text-gray-500 text-sm mb-8">Kami akan mengirimkan link reset ke email Anda.</p>

            @if (session('status'))
            <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-6 flex items-start gap-2">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm font-medium">{{ session('status') }}</p>
            </div>
            @endif

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-6">
                @foreach($errors->all() as $error)
                    <p class="text-sm text-red-600">{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Alamat Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                        class="w-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-400 bg-red-50 @enderror"
                        placeholder="nama@email.com" required autofocus>
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 px-6 rounded-xl transition-colors shadow-sm text-base">
                    Kirim Link Reset Password
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-gray-100 text-center">
                <p class="text-sm text-gray-500">
                    Ingat password?
                    <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-700 font-semibold ml-1">Masuk sekarang</a>
                </p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
