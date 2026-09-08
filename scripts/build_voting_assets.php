<?php

declare(strict_types=1);

// Local asset export only; never called by the application or deployment runtime.
$root = dirname(__DIR__);
$source = $root.'/imagen/voting-illustration-source.png';
$destination = $root.'/public/assets/flowerflow/landing';

if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
    throw new RuntimeException('El export requiere GD con soporte WebP.');
}

$image = imagecreatefrompng($source);
if ($image === false) {
    throw new RuntimeException('No se pudo abrir el original de la ilustración.');
}

foreach ([640, 1024] as $width) {
    $height = (int) round(imagesy($image) * $width / imagesx($image));
    $resized = imagecreatetruecolor($width, $height);
    imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
    $path = $destination.'/voting-illustration-'.$width.'.webp';
    if (! imagewebp($resized, $path, 86)) {
        throw new RuntimeException('No se pudo exportar '.$path);
    }
    imagedestroy($resized);
    printf("%s %dx%d %d bytes SHA-256 %s\n", basename($path), $width, $height, filesize($path), hash_file('sha256', $path));
}

imagedestroy($image);
