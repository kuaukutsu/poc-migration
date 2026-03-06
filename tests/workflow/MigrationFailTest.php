<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\workflow;

use Override;
use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\driver\PdoDriver;
use kuaukutsu\poc\migration\exception\ActionException;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\internal\command\CommandInterface;
use kuaukutsu\poc\migration\internal\command\Params;
use kuaukutsu\poc\migration\tests\MigratorFactory;
use kuaukutsu\poc\migration\MigratorInterface;
use kuaukutsu\poc\migration\InputArgs;

final class MigrationFailTest extends TestCase
{
    private MigratorInterface $migrator;

    private CommandInterface $command;

    #[Override]
    protected function setUp(): void
    {
        $driver = new PdoDriver(
            dsn: 'sqlite::memory:',
        );

        $this->migrator = MigratorFactory::makeFromEvent($driver);
        $this->command = $driver->makeCommand(new Params(table: 'migration'));
    }

    public function testUpExactlyAll(): void
    {
        $this->migrator->init();
        $data = $this->command->fetchApplied();
        self::assertEmpty($data);

        $this->migrator->up(new InputArgs(limit: 1));
        $data = $this->command->fetchApplied();
        self::assertCount(1, $data);

        $this->migrator->down();
        $data = $this->command->fetchApplied();
        self::assertEmpty($data);

        try {
            $this->migrator->up(new InputArgs());
        } catch (ActionException) {
        }

        // только первая миграция успешно
        $data = $this->command->fetchApplied();
        self::assertCount(1, $data);

        $this->migrator->down();
        $data = $this->command->fetchApplied();
        self::assertEmpty($data);

        try {
            $this->migrator->up(new InputArgs(exactlyAll: true));
        } catch (ActionException) {
        }

        // всё или ничего
        $data = $this->command->fetchApplied();
        self::assertEmpty($data);
    }

    public function testUpExactlyAllException(): void
    {
        $this->migrator->init();

        $this->expectException(ActionException::class);
        $this->expectExceptionMessage(
            '202501021025_account_error.sql: SQLSTATE[HY000]: General error: 1 no such table: account'
        );

        $this->migrator->up(new InputArgs(exactlyAll: true));
    }

    public function testMigrationFixtureException(): void
    {
        $this->migrator->init();

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageMatches(
            '/^the directory .+ does not exist.$/i'
        );

        $this->migrator->fixture();
    }
}
