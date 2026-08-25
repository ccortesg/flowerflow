<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! $app->environment('testing')
    || config('database.default') !== 'mysql'
    || config('database.connections.mysql.host') !== '127.0.0.1'
    || config('database.connections.mysql.database') !== 'flowerflow_testing'
    || config('database.connections.mysql.username') !== 'flowerflow_testing_user') {
    fwrite(STDERR, "Guard de base de datos rechazado.\n");
    exit(2);
}

$path = $argv[1] ?? '';
if ($path === '' || ! str_starts_with(realpath(dirname($path)) ?: '', realpath(dirname(__DIR__).'/.runtime') ?: '__missing__')) {
    fwrite(STDERR, "La configuración temporal debe vivir en video-tutorial/.runtime.\n");
    exit(2);
}

$connection = config('database.connections.mysql');
$escape = static fn (mixed $value): string => '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $value).'"';
$contents = "[client]\n"
    .'host='.$escape($connection['host'])."\n"
    .'port='.$escape($connection['port'] ?? 3306)."\n"
    .'user='.$escape($connection['username'])."\n"
    .'password='.$escape($connection['password'])."\n"
    ."protocol=TCP\n";

if (file_put_contents($path, $contents, LOCK_EX) === false) {
    fwrite(STDERR, "No se pudo crear la configuración temporal.\n");
    exit(2);
}
chmod($path, 0600);
