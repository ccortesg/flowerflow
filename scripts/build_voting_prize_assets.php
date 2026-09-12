<?php

declare(strict_types=1);

// Local asset export only; keep the user-supplied originals unchanged.
$root = dirname(__DIR__);
$destination = $root.'/public/assets/flowerflow/landing';

if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
    throw new RuntimeException('El export requiere GD con soporte WebP.');
}

foreach (['metaquest3s' => [480, 960], 'beats-solo4' => [320, 640]] as $name => $widths) {
    $source = $root.'/imagen/prizes/'.$name.'.png';
    $image = imagecreatefrompng($source);
    if ($image === false) {
        throw new RuntimeException('No se pudo abrir el original '.$name);
    }

    foreach ($widths as $width) {
        $height = (int) round(imagesy($image) * $width / imagesx($image));
        $resized = imagecreatetruecolor($width, $height);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
        $path = $destination.'/prize-'.$name.'-'.$width.'.webp';
        if (! imagewebp($resized, $path, 86)) {
            throw new RuntimeException('No se pudo exportar '.$path);
        }
        imagedestroy($resized);
        printf("%s %dx%d %d bytes SHA-256 %s\n", basename($path), $width, $height, filesize($path), hash_file('sha256', $path));
    }

    imagedestroy($image);
}
