<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\workflow;

use Override;
use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\driver\PdoDriver;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\internal\command\CommandInterface;
use kuaukutsu\poc\migration\internal\command\Params;
use kuaukutsu\poc\migration\tests\MigratorFactory;
use kuaukutsu\poc\migration\MigratorInterface;
use kuaukutsu\poc\migration\InputArgs;

/**
 * Верхнеуровневая работа приложения.
 */
final class ArgumentsTest extends TestCase
{
    private MigratorInterface $migrator;

    private CommandInterface $command;

    #[Override]
    protected function setUp(): void
    {
        $driver = new PdoDriver(
            dsn: 'sqlite::memory:',
        );

        $this->migrator = MigratorFactory::makeFromDriver($driver);
        $this->command = $driver->makeCommand(new Params(table: 'migration'));
    }

    public function testUpWithLimit(): void
    {
        $this->migrator->init();
        $data = $this->command->fetchAppliedMigrations();
        self::assertEmpty($data);

        $this->migrator->up(new InputArgs(limit: 1));
        $data = $this->command->fetchAppliedMigrations();
        self::assertCount(1, $data);
        self::assertNotEmpty($data['202501011024_entity_create.sql']);

        $this->migrator->up(new InputArgs(limit: 2));
        $data = $this->command->fetchAppliedMigrations();
        self::assertCount(3, $data);

        $names = array_keys($data);
        self::assertEquals('202501021025_account_email.sql', $names[0]);
        self::assertEquals('202501021024_account_create.sql', $names[1]);
        self::assertEquals('202501011024_entity_create.sql', $names[2]);
    }

    public function testDownWithLimit(): void
    {
        $this->migrator->init();
        $data = $this->command->fetchAppliedMigrations();
        self::assertEmpty($data);

        $this->migrator->up();

        $this->migrator->down(new InputArgs(limit: 1));
        $data = $this->command->fetchAppliedMigrations();
        self::assertCount(2, $data);

        $this->migrator->down(new InputArgs(limit: 2));
        $data = $this->command->fetchAppliedMigrations();
        self::assertEmpty($data);
    }

    public function testWithDryRun(): void
    {
        $this->migrator->init();
        $data = $this->command->fetchAppliedMigrations();
        self::assertEmpty($data);

        $this->migrator->up();
        $data = $this->command->fetchAppliedMigrations();
        self::assertCount(3, $data);

        $this->migrator->down(new InputArgs(dryRun: true));
        $data = $this->command->fetchAppliedMigrations();
        self::assertCount(3, $data);

        $this->migrator->down();
        $data = $this->command->fetchAppliedMigrations();
        self::assertEmpty($data);
    }

    public function testWithDb(): void
    {
        $this->migrator->init();
        $data = $this->command->fetchAppliedMigrations();
        self::assertEmpty($data);

        $this->migrator->up(new InputArgs(limit: 2, dbName: 'sqlite/memory'));
        $data = $this->command->fetchAppliedMigrations();
        self::assertCount(2, $data);
    }

    public function testWithUnknownDb(): void
    {
        $this->migrator->init();
        $data = $this->command->fetchAppliedMigrations();
        self::assertEmpty($data);

        $this->expectException(ConfigurationException::class);
        $this->migrator->up(new InputArgs(dbName: 'sqlite/unknown'));
    }
}
