<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 — Halaman Tidak Ditemukan | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="text-center px-6 py-12">
        <p class="text-6xl font-bold text-blue-500 mb-4">404</p>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Halaman Tidak Ditemukan</h1>
        <p class="text-gray-500 mb-8">
            Halaman yang Anda cari tidak ada atau sudah dipindahkan.
        </p>
        <div class="flex justify-center gap-4">
            <a href="{{ url()->previous() }}"
               class="px-5 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors text-sm">
                &larr; Kembali
            </a>
            <a href="{{ url('/') }}"
               class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-semibold">
                Ke Beranda
            </a>
        </div>
    </div>
</body>
</html>
