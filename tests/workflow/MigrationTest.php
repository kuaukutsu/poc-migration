<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\workflow;

use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\exception\ConnectionException;
use Override;
use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\connection\Command;
use kuaukutsu\poc\migration\connection\Params;
use kuaukutsu\poc\migration\connection\PdoDriver;
use kuaukutsu\poc\migration\exception\InitializationException;
use kuaukutsu\poc\migration\MigratorInterface;
use kuaukutsu\poc\migration\tests\MigratorFactory;

/**
 * Верхнеуровневая работа приложения.
 */
final class MigrationTest extends TestCase
{
    private MigratorInterface $migrator;

    private Command $command;

    #[Override]
    protected function setUp(): void
    {
        $driver = new PdoDriver(
            dsn: 'sqlite::memory:',
        );

        $this->migrator = MigratorFactory::makeFromDriver($driver);
        $this->command = $driver->makeCommand(new Params(table: 'migration'));
    }

    public function testUp(): void
    {
        $this->migrator->init();
        $data = $this->command->fetchSavedMigrationNames();
        self::assertEmpty($data);

        $this->migrator->up();
        $data = $this->command->fetchSavedMigrationNames();
        self::assertCount(3, $data);
        self::assertEquals('202501021025_account_email.sql', $data[0]);
        self::assertEquals('202501021024_account_create.sql', $data[1]);
        self::assertEquals('202501011024_entity_create.sql', $data[2]);

        $this->migrator->up();
        $data = $this->command->fetchSavedMigrationNames();
        self::assertCount(3, $data);
    }

    public function testDown(): void
    {
        $this->migrator->init();
        $data = $this->command->fetchSavedMigrationNames();
        self::assertEmpty($data);

        $this->migrator->up();
        $data = $this->command->fetchSavedMigrationNames();
        self::assertCount(3, $data);

        $this->migrator->down();
        $data = $this->command->fetchSavedMigrationNames();
        self::assertEmpty($data);
    }

    public function testRedo(): void
    {
        $this->migrator->init();
        $data = $this->command->fetchSavedMigrationNames();
        self::assertEmpty($data);

        $this->migrator->up();
        $data = $this->command->fetchSavedMigrationNames();
        self::assertCount(3, $data);

        $this->migrator->redo();
        $data = $this->command->fetchSavedMigrationNames();
        self::assertCount(3, $data);
    }

    public function testInitializationException(): void
    {
        $this->expectException(InitializationException::class);
        $this->migrator->up();
    }

    public function testConfigurationException(): void
    {
        $driver = new PdoDriver(
            dsn: 'unknown:',
        );

        $this->expectException(ConfigurationException::class);

        $migrator = MigratorFactory::makeFromDriver($driver);
        $migrator->init();
    }

    public function testConnectionException(): void
    {
        $driver = new PdoDriver(
            dsn: 'mysql:host=mysql;dbname=main',
        );

        $this->expectException(ConnectionException::class);

        $migrator = MigratorFactory::makeFromDriver($driver);
        $migrator->init();
    }
}
