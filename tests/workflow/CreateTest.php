<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\workflow;

use Override;
use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\internal\command\Params;
use kuaukutsu\poc\migration\internal\connection\PDO\Driver;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\tests\MigratorFactory;
use kuaukutsu\poc\migration\InputArgs;

final class CreateTest extends TestCase
{
    public function testCreate(): void
    {
        $driver = new Driver(
            dsn: 'sqlite::memory:',
        );

        $path = dirname(__DIR__) . '/migration/sqlite/memory';
        $migrator = MigratorFactory::makeFromEvent(driver: $driver, path: $path);
        $command = $driver->makeCommand(new Params(table: 'migration'));

        $migrator->init();

        $migrator->up();
        $countMigration = count($command->fetchApplied());

        $migrator->create(new InputArgs(dbName: 'sqlite/memory', migrationName: 'test'));
        $migrator->create(new InputArgs(dbName: 'sqlite/memory', migrationName: '2test'));

        $migrator->up();
        self::assertCount($countMigration + 2, $command->fetchApplied());
    }

    public function testCreateException(): void
    {
        $driver = new Driver(
            dsn: 'sqlite::memory:',
        );

        $path = dirname(__DIR__) . '/migration/sqlite/non';
        $migrator = MigratorFactory::makeFromEvent(driver: $driver, path: $path);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageMatches('/^the dir .+ is not writable or does not exist.$/i');

        $migrator->create(new InputArgs(dbName: 'sqlite/memory', migrationName: 'test'));
    }

    #[Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        $pattern = dirname(__DIR__) . '/migration/sqlite/memory/*test.sql';
        foreach (glob($pattern) ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
