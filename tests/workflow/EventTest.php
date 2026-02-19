<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\workflow;

use Throwable;
use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\driver\PdoDriver;
use kuaukutsu\poc\migration\event\Event;
use kuaukutsu\poc\migration\InputArgs;
use kuaukutsu\poc\migration\tests\MigratorFactory;
use kuaukutsu\poc\migration\tests\stub\TestSubscriber;

final class EventTest extends TestCase
{
    public function testSelectDatabaseError(): void
    {
        $eventSubscriber = new TestSubscriber();
        $migrator = MigratorFactory::makeFromEvent(
            new PdoDriver(
                dsn: 'sqlite::memory:',
            ),
            [
                $eventSubscriber,
            ]
        );

        try {
            $migrator->init();
            $migrator->up(new InputArgs(dbName: 'test'));
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
            new PdoDriver(
                dsn: 'mysql::memory:',
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
            new PdoDriver(
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
            new PdoDriver(
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

        self::assertStringContainsString(
            '202501011024_entity_create.sql',
            $eventSubscriber->get(Event::MigrateSuccess)
        );

        self::assertStringContainsString(
            '202501021025_account_error.sql',
            $eventSubscriber->get(Event::MigrateError)
        );
    }

    public function testMigrationDryRun(): void
    {
        $eventSubscriber = new TestSubscriber();
        $migrator = MigratorFactory::makeFromEvent(
            new PdoDriver(
                dsn: 'sqlite::memory:',
            ),
            [
                $eventSubscriber,
            ]
        );

        $migrator->init();

        try {
            $migrator->up(new InputArgs(dryRun: true));
        } catch (Throwable) {
        }

        self::assertStringContainsString(
            '202501021025_account_error.sql',
            $eventSubscriber->get(Event::MigrateSuccess)
        );

        self::assertStringContainsString(
            'the directory [/app/tests/migration/sqlite/event-repeatable/] does not exist.',
            $eventSubscriber->get(Event::FilesystemNotice)
        );
    }

    public function testMigrationDoesNotContainFiles(): void
    {
        $eventSubscriber = new TestSubscriber();
        $migrator = MigratorFactory::makeFromDriver(
            new PdoDriver(
                dsn: 'sqlite::memory:',
            ),
            [
                $eventSubscriber,
            ]
        );

        $migrator->init();

        $migrator->up();
        // migration completed in the previous step
        $migrator->up();
        self::assertStringContainsString(
            'does not contain migration files',
            $eventSubscriber->get(Event::FilesystemNotice)
        );

        $eventSubscriber->clear();

        $migrator->down();
        // migration completed in the previous step
        $migrator->down();
        self::assertStringContainsString(
            'does not contain migration files',
            $eventSubscriber->get(Event::FilesystemNotice)
        );
    }

    public function testDirectoryDoesNotExistError(): void
    {
        $eventSubscriber = new TestSubscriber();
        $migrator = MigratorFactory::makeFromEvent(
            new PdoDriver(
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
}
