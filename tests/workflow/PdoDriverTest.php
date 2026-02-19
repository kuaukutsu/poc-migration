<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\workflow;

use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\driver\PdoDriver;

final class PdoDriverTest extends TestCase
{
    public function testTypeDriver(): void
    {
        $driver = new PdoDriver(
            dsn: 'pgsql:host=postgres;port=5432;dbname=main',
        );

        self::assertEquals('pgsql', $driver->getName());

        $driver = new PdoDriver(
            dsn: 'mysql:host=mysql;dbname=main',
        );

        self::assertEquals('mysql', $driver->getName());

        $driver = new PdoDriver(
            dsn: 'sqlite::memory:',
        );

        self::assertEquals('sqlite', $driver->getName());
    }

    public function testConfigurationException(): void
    {
        $this->expectException(ConfigurationException::class);

        new PdoDriver(
            dsn: 'unknown:',
        );
    }
}
