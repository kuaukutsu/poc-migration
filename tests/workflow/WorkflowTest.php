<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\workflow;

use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\exception\InitializationException;
use kuaukutsu\poc\migration\tests\MigratorFactory;
use kuaukutsu\poc\migration\connection\Command;
use kuaukutsu\poc\migration\connection\PdoDriver;
use kuaukutsu\poc\migration\connection\Params;
use kuaukutsu\poc\migration\Migrator;

final class WorkflowTest extends TestCase
{
    private Migrator $migrator;

    private Command $command;

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
        self::assertCount(1, $data);
        self::assertEquals('202501011024_entity_create.sql', $data[0]);

        $this->migrator->up();
        $data = $this->command->fetchSavedMigrationNames();
        self::assertCount(1, $data);
    }

    public function testDown(): void
    {
        $this->migrator->init();
        $data = $this->command->fetchSavedMigrationNames();
        self::assertEmpty($data);

        $this->migrator->up();
        $data = $this->command->fetchSavedMigrationNames();
        self::assertCount(1, $data);
        self::assertEquals('202501011024_entity_create.sql', $data[0]);

        $this->migrator->down();
        $data = $this->command->fetchSavedMigrationNames();
        self::assertEmpty($data);
    }

    public function testInitException(): void
    {
        $this->expectException(InitializationException::class);

        $this->migrator->up();
    }
}
