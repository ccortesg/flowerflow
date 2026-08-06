<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\EnsuresDisposableDatabase;

class DisposableDatabaseGuardTest extends TestCase
{
    public function test_it_accepts_the_approved_local_mysql_database(): void
    {
        EnsuresDisposableDatabase::assertSettingsAreSafe($this->safeSettings());

        $this->addToAssertionCount(1);
    }

    public function test_it_rejects_a_non_testing_environment(): void
    {
        $this->expectException(RuntimeException::class);

        EnsuresDisposableDatabase::assertSettingsAreSafe([
            ...$this->safeSettings(),
            'environment' => 'local',
        ]);
    }

    public function test_it_rejects_a_non_mysql_connection(): void
    {
        $this->expectException(RuntimeException::class);

        EnsuresDisposableDatabase::assertSettingsAreSafe([
            ...$this->safeSettings(),
            'connection' => 'sqlite',
            'driver' => 'sqlite',
        ]);
    }

    public function test_it_rejects_the_primary_database(): void
    {
        $this->expectException(RuntimeException::class);

        EnsuresDisposableDatabase::assertSettingsAreSafe([
            ...$this->safeSettings(),
            'database' => 'flowerflow',
        ]);
    }

    public function test_it_rejects_a_remote_database_host(): void
    {
        $this->expectException(RuntimeException::class);

        EnsuresDisposableDatabase::assertSettingsAreSafe([
            ...$this->safeSettings(),
            'host' => 'mysql.internal.example',
        ]);
    }

    public function test_it_rejects_a_database_url_override(): void
    {
        $this->expectException(RuntimeException::class);

        EnsuresDisposableDatabase::assertSettingsAreSafe([
            ...$this->safeSettings(),
            'url' => 'mysql://override.invalid/flowerflow_testing',
        ]);
    }

    /**
     * @return array{environment: string, connection: string, driver: string, host: string, database: string, url: null}
     */
    private function safeSettings(): array
    {
        return [
            'environment' => 'testing',
            'connection' => 'mysql',
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'database' => EnsuresDisposableDatabase::DATABASE,
            'url' => null,
        ];
    }
}
