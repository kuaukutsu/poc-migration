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
        $data = $this->command->fetchApplied();
        self::assertEmpty($data);
    }

    #[Depends('testInit')]
    public function testUp(): void
    {
        $this->migrator->init();

        $this->migrator->up();
        $data = $this->command->fetchApplied();
        $countMigration = count($data);
        self::assertGreaterThanOrEqual(3, $countMigration);

        // sort order
        $names = array_keys($data);
        self::assertEquals('202501021025_account_email.sql', $names[0]);
        self::assertEquals('202501021024_account_create.sql', $names[1]);
        self::assertEquals('202501011024_entity_create.sql', $names[2]);

        $this->migrator->up();
        $data = $this->command->fetchApplied();
        self::assertCount($countMigration, $data);
    }

    #[Depends('testInit')]
    public function testDown(): void
    {
        $this->migrator->init();

        $this->migrator->up();
        $data = $this->command->fetchApplied();
        self::assertGreaterThanOrEqual(3, $data);

        $this->migrator->down();
        $data = $this->command->fetchApplied();
        self::assertEmpty($data);
    }

    #[Depends('testInit')]
    public function testRedo(): void
    {
        $this->migrator->init();

        $this->migrator->up();
        $data = $this->command->fetchApplied();
        self::assertGreaterThanOrEqual(3, $data);

        $version = (int)current($data);
        self::assertGreaterThan(0, $version);

        usleep(10_000);

        $this->migrator->redo();
        $data = $this->command->fetchApplied();
        self::assertGreaterThanOrEqual(3, $data);

        $versionNew = (int)current($data);
        self::assertGreaterThan(0, $versionNew);

        // новая версия больше старой
        self::assertGreaterThan($version, $versionNew);
    }

    public function testInitializationException(): void
    {
        $this->expectException(InitializationException::class);
        $this->expectExceptionMessage('Error reading system data.');

        $this->migrator->up();
    }

    public function testConnectionException(): void
    {
        $driver = new PdoDriver(dsn: 'mysql:host=mysql;dbname=main');
        $migrator = MigratorFactory::makeFromEvent($driver);

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessageMatches('~PDO_MYSQL:\w+~');

        $migrator->init();
    }

    public function testActionException(): void
    {
        $driver = new PdoDriver(dsn: 'sqlite::memory:');
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
        $driver = new PdoDriver(dsn: 'sqlite::memory:');
        $migrator = MigratorFactory::makeFromEvent($driver);
        $command = $driver->makeCommand(new Params(table: 'migration'));

        $migrator->init();

        $migrator->up(new InputArgs(dryRun: true));
        $data = $command->fetchApplied();
        self::assertEmpty($data);
    }
}
