<?php

$width = 1200;
$height = 630;
$image = imagecreatetruecolor($width, $height);
imagealphablending($image, true);
imagesavealpha($image, true);

function rgb(array $color): array
{
    return $color;
}

function allocate($image, array $color): int
{
    return imagecolorallocate($image, $color[0], $color[1], $color[2]);
}

function allocateAlpha($image, array $color, int $alpha): int
{
    return imagecolorallocatealpha($image, $color[0], $color[1], $color[2], $alpha);
}

function findFont(array $candidates): string
{
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    throw new RuntimeException('No TTF font found for OG image generation.');
}

$fontBold = findFont([
    'C:/Windows/Fonts/arialbd.ttf',
    'C:/Windows/Fonts/Arialbd.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
]);

$fontRegular = findFont([
    'C:/Windows/Fonts/arial.ttf',
    'C:/Windows/Fonts/Arial.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
]);

for ($x = 0; $x < $width; $x++) {
    $ratio = $x / $width;
    $r = (int) (15 + (30 - 15) * $ratio);
    $g = (int) (23 + (58 - 23) * $ratio);
    $b = (int) (42 + (95 - 42) * $ratio);
    imageline($image, $x, 0, $x, $height, imagecolorallocate($image, $r, $g, $b));
}

imagefilledellipse($image, 1060, 72, 360, 360, allocateAlpha($image, rgb([59, 130, 246]), 96));
imagefilledellipse($image, 170, 590, 300, 300, allocateAlpha($image, rgb([99, 102, 241]), 104));
imagefilledpolygon($image, [815, 0, 1200, 0, 1200, 630, 980, 630], allocateAlpha($image, rgb([37, 99, 235]), 93));

$white = allocate($image, rgb([255, 255, 255]));
$blue = allocate($image, rgb([96, 165, 250]));
$softBlue = allocate($image, rgb([147, 197, 253]));
$muted = allocate($image, rgb([226, 232, 240]));
$green = allocate($image, rgb([52, 211, 153]));
$darkBlue = allocate($image, rgb([29, 78, 216]));
$panel = allocateAlpha($image, rgb([15, 23, 42]), 78);

imagefilledrectangle($image, 80, 170, 170, 260, $darkBlue);
imagerectangle($image, 80, 170, 170, 260, $blue);
imagefilledellipse($image, 108, 198, 8, 8, $softBlue);
imageellipse($image, 108, 198, 22, 22, $softBlue);
imageline($image, 118, 198, 158, 222, $softBlue);
imagefilledellipse($image, 108, 228, 6, 6, $blue);
imageellipse($image, 108, 228, 18, 18, $blue);
imagefilledellipse($image, 128, 228, 6, 6, $blue);
imageellipse($image, 128, 228, 18, 18, $blue);
imageline($image, 116, 222, 162, 188, $blue);
imageline($image, 116, 234, 162, 200, $blue);

imagettftext($image, 58, 0, 185, 233, $white, $fontBold, 'ZRINT');
imagettftext($image, 58, 0, 445, 233, $blue, $fontBold, 'TAILOR');
imagettftext($image, 24, 0, 80, 305, $softBlue, $fontRegular, 'Platform jasa jahit online untuk pakaian custom');
imagettftext($image, 24, 0, 80, 342, $softBlue, $fontRegular, 'dengan pengukuran multi-view berbasis Computer Vision.');
imagefilledrectangle($image, 80, 375, 220, 379, $blue);

$features = [
    'Pengukuran otomatis via Computer Vision AI',
    'Tracking pesanan real-time',
    'Notifikasi WhatsApp otomatis',
];

$y = 432;
foreach ($features as $feature) {
    imagefilledellipse($image, 93, $y - 6, 12, 12, $green);
    imagettftext($image, 20, 0, 110, $y, $muted, $fontRegular, $feature);
    $y += 45;
}

imagefilledrectangle($image, 870, 190, 1110, 440, $panel);
imagerectangle($image, 870, 190, 1110, 440, $blue);
imagettftext($image, 74, 0, 944, 310, $blue, $fontBold, 'CV');
imagettftext($image, 20, 0, 915, 360, $muted, $fontRegular, 'Ukur Badan');
imagettftext($image, 20, 0, 908, 394, $muted, $fontRegular, 'Multi-view');
imagettftext($image, 16, 0, 825, 585, $blue, $fontRegular, 'zrinttailor online tailoring system');

imagepng($image, __DIR__ . '/../public/og-image.png', 9);
