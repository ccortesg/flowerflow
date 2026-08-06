<?php

namespace Tests\Support;

use Illuminate\Foundation\Application;
use RuntimeException;

final class EnsuresDisposableDatabase
{
    public const DATABASE = 'flowerflow_testing';

    public const USERNAME = 'flowerflow_testing_user';

    /**
     * @param  array{environment: mixed, connection: mixed, driver: mixed, host: mixed, database: mixed, username: mixed, url: mixed}  $settings
     */
    public static function assertSettingsAreSafe(array $settings): void
    {
        if ($settings['environment'] !== 'testing') {
            throw new RuntimeException('La suite requiere APP_ENV=testing.');
        }

        if ($settings['connection'] !== 'mysql' || $settings['driver'] !== 'mysql') {
            throw new RuntimeException('La suite requiere la conexión MySQL de pruebas.');
        }

        if (! in_array($settings['host'], ['127.0.0.1', 'localhost', '::1'], true)) {
            throw new RuntimeException('La suite sólo admite MySQL local.');
        }

        if ($settings['database'] !== self::DATABASE) {
            throw new RuntimeException('La suite no puede operar fuera de flowerflow_testing.');
        }

        if ($settings['username'] !== self::USERNAME) {
            throw new RuntimeException('La suite requiere el usuario MySQL exclusivo flowerflow_testing_user.');
        }

        if (filled($settings['url'])) {
            throw new RuntimeException('DB_URL no está permitido durante las pruebas locales.');
        }
    }

    public static function assertApplicationIsSafe(Application $app): void
    {
        $connection = (string) $app['config']->get('database.default');
        $settings = (array) $app['config']->get("database.connections.{$connection}", []);

        self::assertSettingsAreSafe([
            'environment' => $app->environment(),
            'connection' => $connection,
            'driver' => $settings['driver'] ?? null,
            'host' => $settings['host'] ?? null,
            'database' => $settings['database'] ?? null,
            'username' => $settings['username'] ?? null,
            'url' => $settings['url'] ?? null,
        ]);
    }
}
