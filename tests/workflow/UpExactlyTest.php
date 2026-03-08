<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\workflow;

use Override;
use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\command\CommandInterface;
use kuaukutsu\poc\migration\exception\ActionException;
use kuaukutsu\poc\migration\internal\connection\PDO\Driver;
use kuaukutsu\poc\migration\tests\MigratorFactory;
use kuaukutsu\poc\migration\Config;
use kuaukutsu\poc\migration\InputOptions;
use kuaukutsu\poc\migration\MigratorInterface;

final class UpExactlyTest extends TestCase
{
    private MigratorInterface $migrator;

    private CommandInterface $command;

    #[Override]
    protected function setUp(): void
    {
        $driver = new Driver(
            dsn: 'sqlite::memory:',
        );

        $this->migrator = MigratorFactory::makeFromEvent($driver);
        $this->command = $driver->makeCommand(new Config(table: 'migration'));
    }

    public function testUpExactlyAll(): void
    {
        $this->migrator->init();
        $data = $this->command->fetchApplied();
        self::assertEmpty($data);

        $this->migrator->up(new InputOptions(limit: 1));
        $data = $this->command->fetchApplied();
        self::assertCount(1, $data);

        $this->migrator->down();
        $data = $this->command->fetchApplied();
        self::assertEmpty($data);

        try {
            $this->migrator->up();
        } catch (ActionException) {
        }

        // только первая миграция успешно
        $data = $this->command->fetchApplied();
        self::assertCount(1, $data);

        $this->migrator->down();
        $data = $this->command->fetchApplied();
        self::assertEmpty($data);

        try {
            $this->migrator->up(new InputOptions(exactlyAll: true));
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
            'SQLSTATE[HY000]: General error: 1 no such table:'
        );

        $this->migrator->up(new InputOptions(exactlyAll: true));
    }

    public function testVerify(): void
    {
        $this->migrator->init();
        $data = $this->command->fetchApplied();
        self::assertEmpty($data);

        $this->migrator->up(new InputOptions(limit: 1));
        $data = $this->command->fetchApplied();
        self::assertCount(1, $data);

        // точность версионирования
        usleep(10_000);

        try {
            $this->migrator->verify();
        } catch (ActionException) {
        }

        // только первая миграция успешно
        $data = $this->command->fetchApplied();
        self::assertCount(1, $data);
    }

    public function testVerifyException(): void
    {
        $this->migrator->init();

        $this->expectException(ActionException::class);
        $this->expectExceptionMessage(
            'SQLSTATE[HY000]: General error: 1 no such table:'
        );

        $this->migrator->verify();
    }
}
