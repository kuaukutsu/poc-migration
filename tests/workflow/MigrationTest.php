<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\workflow;

use kuaukutsu\poc\migration\InputArgs;
use Override;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\driver\PdoDriver;
use kuaukutsu\poc\migration\exception\ActionException;
use kuaukutsu\poc\migration\exception\ConnectionException;
use kuaukutsu\poc\migration\exception\InitializationException;
use kuaukutsu\poc\migration\internal\command\CommandInterface;
use kuaukutsu\poc\migration\internal\command\Params;
use kuaukutsu\poc\migration\MigratorInterface;
use kuaukutsu\poc\migration\tests\MigratorFactory;

/**
 * Верхнеуровневая работа приложения.
 */
final class MigrationTest extends TestCase
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

    public function testInit(): void
    {
        $this->migrator->init();
        $data = $this->command->fetchSavedMigrationNames();
        self::assertEmpty($data);
    }

    #[Depends('testInit')]
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

    #[Depends('testInit')]
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

    #[Depends('testInit')]
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
        $this->expectExceptionMessage('Error reading system data.');

        $this->migrator->up();
    }

    public function testConnectionException(): void
    {
        $driver = new PdoDriver(
            dsn: 'mysql:host=mysql;dbname=main',
        );

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessageMatches('~PDO_MYSQL:\w+~');

        $migrator = MigratorFactory::makeFromDriver($driver);
        $migrator->init();
    }

    public function testActionException(): void
    {
        $migrator = MigratorFactory::makeFromEvent(
            new PdoDriver(
                dsn: 'sqlite::memory:',
            )
        );

        $this->expectException(ActionException::class);
        $this->expectExceptionMessage(
            '202501021025_account_error.sql: SQLSTATE[HY000]: General error: 1 no such table: account'
        );

        $migrator->init();
        $migrator->up();
    }

    public function testActionExceptionDryRun(): void
    {
        $driver = new PdoDriver(dsn: 'sqlite::memory:');
        $migrator = MigratorFactory::makeFromEvent($driver);
        $command = $driver->makeCommand(new Params(table: 'migration'));

        $migrator->init();
        $data = $command->fetchSavedMigrationNames();
        self::assertEmpty($data);

        $migrator->up(new InputArgs(dryRun: true));
        $data = $command->fetchSavedMigrationNames();
        self::assertEmpty($data);
    }
}
