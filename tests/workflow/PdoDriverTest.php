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
        self::assertEquals('main', $driver->getDbName());

        $driver = new PdoDriver(
            dsn: 'MYSQL:host=mysql;dbname=copyDb',
        );

        self::assertEquals('mysql', $driver->getName());
        self::assertEquals('copydb', $driver->getDbName());

        $driver = new PdoDriver(
            dsn: 'sqlite::memory:',
        );

        self::assertEquals('sqlite', $driver->getName());
        self::assertEquals('memory', $driver->getDbName());

        $driver = new PdoDriver(
            dsn: 'sqlite:tests/data/sqlite/db.sqlite3',
        );

        self::assertEquals('sqlite', $driver->getName());
        self::assertEquals('db', $driver->getDbName());
    }

    public function testConfigurationException(): void
    {
        $this->expectException(ConfigurationException::class);

        new PdoDriver(
            dsn: 'unknown:',
        );
    }

    public function testDSNConfigurationException(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('PDODriver: dsn is incorrect.');

        new PdoDriver(
            dsn: 'mysql::memory:',
        );
    }

    public function testDSNEmptyConfigurationException(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('PDODriver: dsn is incorrect.');

        new PdoDriver(
            dsn: 'sqlite:',
        );
    }
}
