@php
    $metaTitle = trim($__env->yieldContent('meta-title'))
        ?: trim($__env->yieldContent('page-title'))
        ?: 'Platform Jasa Jahit Online';
    $metaTitle = str_contains($metaTitle, 'ZRINTTAILOR') ? $metaTitle : $metaTitle . ' - ZRINTTAILOR';

    $metaDescription = trim($__env->yieldContent('meta-description'))
        ?: 'ZRINTTAILOR adalah platform jasa jahit online untuk pesan pakaian custom, ukur badan multi-view, tracking pesanan real-time, dan validasi ukuran berbasis Computer Vision.';

    $metaImage = url('/og-image.png');
    $metaUrl = url()->current();
@endphp

<meta name="description" content="{{ $metaDescription }}">
<meta name="theme-color" content="#1e3a5f">

<meta property="og:type" content="website">
<meta property="og:url" content="{{ $metaUrl }}">
<meta property="og:title" content="{{ $metaTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:image" content="{{ $metaImage }}">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="id_ID">
<meta property="og:site_name" content="ZRINTTAILOR">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $metaTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
<meta name="twitter:image" content="{{ $metaImage }}">
