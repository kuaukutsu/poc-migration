<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\workflow;

use Throwable;
use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\internal\connection\PDO\Driver;
use kuaukutsu\poc\migration\event\Event;
use kuaukutsu\poc\migration\InputOptions;
use kuaukutsu\poc\migration\tests\MigratorFactory;
use kuaukutsu\poc\migration\tests\stub\TestSubscriber;

final class EventTest extends TestCase
{
    public function testSelectDatabaseError(): void
    {
        $eventSubscriber = new TestSubscriber();
        $migrator = MigratorFactory::makeFromEvent(
            new Driver(
                dsn: 'sqlite::memory:',
            ),
            [
                $eventSubscriber,
            ]
        );

        try {
            $migrator->init();
            $migrator->up(new InputOptions(dbName: 'test'));
        } catch (Throwable) {
        }

        self::assertEquals(
            '[test] no such database in the configuration.',
            $eventSubscriber->get(Event::ConfigurationError)
        );
    }

    public function testConnectionError(): void
    {
        $eventSubscriber = new TestSubscriber();
        $migrator = MigratorFactory::makeFromEvent(
            new Driver(
                dsn: 'mysql:host=example;dbname=example',
            ),
            [
                $eventSubscriber,
            ]
        );

        try {
            $migrator->init();
        } catch (Throwable) {
        }

        self::assertStringContainsString(
            'PDO_MYSQL:',
            $eventSubscriber->get(Event::ConnectionError)
        );
    }

    public function testInitializationError(): void
    {
        $eventSubscriber = new TestSubscriber();
        $migrator = MigratorFactory::makeFromEvent(
            new Driver(
                dsn: 'sqlite::memory:',
            ),
            [
                $eventSubscriber,
            ]
        );

        try {
            $migrator->up();
        } catch (Throwable) {
        }

        self::assertStringContainsString(
            'General error: 1 no such table',
            $eventSubscriber->get(Event::InitializationError)
        );
    }

    public function testMigration(): void
    {
        $eventSubscriber = new TestSubscriber();
        $migrator = MigratorFactory::makeFromEvent(
            new Driver(
                dsn: 'sqlite::memory:',
            ),
            [
                $eventSubscriber,
            ]
        );

        $migrator->init();

        try {
            $migrator->up();
        } catch (Throwable) {
        }

        $version = substr((string)time(), 0, -2);

        self::assertStringContainsString(
            '202501011024_entity_create.sql, vers: ' . $version,
            $eventSubscriber->get(Event::MigrateSuccess)
        );

        self::assertStringContainsString(
            '202501021025_account_error.sql, vers: ' . $version,
            $eventSubscriber->get(Event::MigrateError)
        );
    }

    public function testMigrationDryRun(): void
    {
        $eventSubscriber = new TestSubscriber();
        $migrator = MigratorFactory::makeFromEvent(
            new Driver(
                dsn: 'sqlite::memory:',
            ),
            [
                $eventSubscriber,
            ]
        );

        $migrator->init();

        try {
            $migrator->up(new InputOptions(dryRun: true));
        } catch (Throwable) {
        }

        self::assertStringContainsString(
            '202501021025_account_error.sql',
            $eventSubscriber->get(Event::MigrateSuccess)
        );

        // event-repeatable: does not exist, but does not start in dry-run mode
        self::assertStringNotContainsString(
            'does not exist.',
            $eventSubscriber->get(Event::FilesystemNotice)
        );
    }

    public function testMigrationFilesystemNotice(): void
    {
        $eventSubscriber = new TestSubscriber();
        $migrator = MigratorFactory::makeFromEvent(
            new Driver(
                dsn: 'sqlite::memory:',
            ),
            [
                $eventSubscriber,
            ]
        );

        $migrator->init();
        $migrator->up(new InputOptions(limit: 1, hasRepeatable: true));

        // event-repeatable: does not exist
        self::assertStringContainsString(
            'does not exist.',
            $eventSubscriber->get(Event::FilesystemNotice)
        );
    }

    public function testMigrationUpDoesNotContainFiles(): void
    {
        $eventSubscriber = new TestSubscriber();
        $migrator = MigratorFactory::makeFromEvent(
            new Driver(
                dsn: 'sqlite::memory:',
            ),
            [
                $eventSubscriber,
            ],
            dirname(__DIR__) . '/migration/sqlite/memory'
        );

        $migrator->init();
        $migrator->up();

        // migration completed in the previous step
        $migrator->up();
        self::assertStringContainsString(
            'does not contain migration files',
            $eventSubscriber->get(Event::FilesystemNotice)
        );
    }

    public function testMigrationDownDoesNotContainFiles(): void
    {
        $eventSubscriber = new TestSubscriber();
        $migrator = MigratorFactory::makeFromEvent(
            new Driver(
                dsn: 'sqlite::memory:',
            ),
            [
                $eventSubscriber,
            ],
            dirname(__DIR__) . '/migration/sqlite/memory'
        );

        $migrator->init();
        $migrator->down();

        // migration completed in the previous step
        $migrator->down();
        self::assertStringContainsString(
            'does not contain migration files',
            $eventSubscriber->get(Event::FilesystemNotice)
        );
    }

    public function testFixtureDirectoryDoesNotExistError(): void
    {
        $eventSubscriber = new TestSubscriber();
        $migrator = MigratorFactory::makeFromEvent(
            new Driver(
                dsn: 'sqlite::memory:',
            ),
            [
                $eventSubscriber,
            ]
        );

        $migrator->init();
        try {
            $migrator->fixture();
        } catch (Throwable) {
        }

        self::assertStringContainsString(
            'does not exist',
            $eventSubscriber->get(Event::FilesystemError)
        );
    }

    public function testCreateDirectoryDoesNotExistError(): void
    {
        $eventSubscriber = new TestSubscriber();
        $migrator = MigratorFactory::makeFromEvent(
            new Driver(
                dsn: 'sqlite::memory:',
            ),
            [
                $eventSubscriber,
            ],
            dirname(__DIR__) . '/migration/sqlite/non'
        );

        $migrator->init();
        try {
            $migrator->create(new InputOptions(dbName: 'sqlite/memory', migrationName: 'test'));
        } catch (Throwable) {
        }

        self::assertStringContainsString(
            'is not writable or does not exist.',
            $eventSubscriber->get(Event::FilesystemError)
        );
    }
}
