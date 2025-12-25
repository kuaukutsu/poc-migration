<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\workflow;

use kuaukutsu\poc\migration\connection\Command;
use kuaukutsu\poc\migration\connection\Params;
use kuaukutsu\poc\migration\connection\PdoDriver;
use kuaukutsu\poc\migration\exception\InitializationException;
use kuaukutsu\poc\migration\MigratorArgs;
use kuaukutsu\poc\migration\Migrator;
use kuaukutsu\poc\migration\tests\MigratorFactory;
use Override;
use PHPUnit\Framework\TestCase;

/**
 * Верхнеуровневая работа приложения.
 */
final class MigrationTest extends TestCase
{
    private Migrator $migrator;

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

    public function testUpWithLimit(): void
    {
        $this->migrator->init();
        $data = $this->command->fetchSavedMigrationNames();
        self::assertEmpty($data);

        $this->migrator->up(new MigratorArgs(limit: 1));
        $data = $this->command->fetchSavedMigrationNames();
        self::assertCount(1, $data);
        self::assertEquals('202501011024_entity_create.sql', $data[0]);

        $this->migrator->up(new MigratorArgs(limit: 2));
        $data = $this->command->fetchSavedMigrationNames();
        self::assertCount(3, $data);
        self::assertEquals('202501021025_account_email.sql', $data[0]);
        self::assertEquals('202501021024_account_create.sql', $data[1]);
        self::assertEquals('202501011024_entity_create.sql', $data[2]);
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

    public function testDownWithLimit(): void
    {
        $this->migrator->init();
        $data = $this->command->fetchSavedMigrationNames();
        self::assertEmpty($data);

        $this->migrator->up();

        $this->migrator->down(new MigratorArgs(limit: 1));
        $data = $this->command->fetchSavedMigrationNames();
        self::assertCount(2, $data);

        $this->migrator->down(new MigratorArgs(limit: 1));
        $data = $this->command->fetchSavedMigrationNames();
        self::assertCount(1, $data);

        $this->migrator->down();
        $data = $this->command->fetchSavedMigrationNames();
        self::assertEmpty($data);
    }

    public function testInitializationException(): void
    {
        $this->expectException(InitializationException::class);
        $this->migrator->up();
    }
}
