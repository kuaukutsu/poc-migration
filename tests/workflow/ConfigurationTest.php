<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\workflow;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\exception\ActionException;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\exception\ConnectionException;
use kuaukutsu\poc\migration\exception\InitializationException;
use kuaukutsu\poc\migration\internal\connection\PDO\Driver;
use kuaukutsu\poc\migration\tests\MigratorFactory;
use kuaukutsu\poc\migration\Config;
use kuaukutsu\poc\migration\InputOptions;

final class ConfigurationTest extends TestCase
{
    public function testUpInitializationException(): void
    {
        $driver = new Driver(dsn: 'sqlite::memory:');
        $migrator = MigratorFactory::makeFromEvent($driver);

        $this->expectException(InitializationException::class);
        $this->expectExceptionMessage('Error reading system data.');

        $migrator->up();
    }

    public function testDownInitializationException(): void
    {
        $driver = new Driver(dsn: 'sqlite::memory:');
        $migrator = MigratorFactory::makeFromEvent($driver);

        $this->expectException(InitializationException::class);
        $this->expectExceptionMessage('Error reading system data.');

        $migrator->down();
    }

    public function testConnectionException(): void
    {
        $driver = new Driver(dsn: 'mysql:host=mysql;dbname=main');
        $migrator = MigratorFactory::makeFromEvent($driver);

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessageMatches('~PDO_MYSQL:\w+~');

        $migrator->init();
    }

    public function testActionException(): void
    {
        $driver = new Driver(dsn: 'sqlite::memory:');
        $migrator = MigratorFactory::makeFromEvent($driver);

        $this->expectException(ActionException::class);
        $this->expectExceptionMessage(
            '202501021025_account_error.sql: SQLSTATE[HY000]: General error: 1 no such table: account'
        );

        $migrator->init();
        $migrator->up();
    }

    public function testActionNotExceptionDryRun(): void
    {
        $driver = new Driver(dsn: 'sqlite::memory:');
        $migrator = MigratorFactory::makeFromEvent($driver);
        $command = $driver->makeCommand(new Config(table: 'migration'));

        $migrator->init();

        $migrator->up(new InputOptions(dryRun: true));
        $data = $command->fetchApplied();
        self::assertEmpty($data);
    }

    public function testFixtureException(): void
    {
        $driver = new Driver(dsn: 'sqlite::memory:');
        $migrator = MigratorFactory::makeFromEvent($driver);

        $migrator->init();

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageMatches(
            '/^the directory .+ does not exist.$/i'
        );

        $migrator->fixture();
    }

    public function testCreateDbNameException(): void
    {
        $driver = new Driver(dsn: 'sqlite::memory:');
        $migrator = MigratorFactory::makeFromEvent($driver);

        $migrator->init();

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('DBName must be declared.');

        $migrator->create();
    }

    public function testCreateMigrationNameException(): void
    {
        $driver = new Driver(dsn: 'sqlite::memory:');
        $migrator = MigratorFactory::makeFromEvent($driver);

        $migrator->init();

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Migration Name must be declared.');

        $migrator->create(new InputOptions(dbName: 'test'));
    }

    /**
     * @return iterable<non-empty-string[]>
     */
    public static function additionTableNameCases(): iterable
    {
        yield ['migrate'];
        yield ['migrate_table'];
        yield ['migrate22'];
    }

    /**
     * @param non-empty-string $tableName
     */
    #[DataProvider('additionTableNameCases')]
    public function testTableName(string $tableName): void
    {
        $config = new Config(table: $tableName);
        self::assertNotEmpty($config);
    }

    /**
     * @return iterable<non-empty-string[]>
     */
    public static function additionTableNameBadCases(): iterable
    {
        yield ['`migrate`'];
        yield ['migrate space'];
        yield ['{}'];
    }

    /**
     * @param non-empty-string $tableName
     */
    #[DataProvider('additionTableNameBadCases')]
    public function testTableNameException(string $tableName): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('contains invalid characters.');

        new Config(table: $tableName);
    }
}
