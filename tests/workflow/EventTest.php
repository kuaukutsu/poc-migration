<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\workflow;

use Throwable;
use PHPUnit\Framework\TestCase;
use kuaukutsu\poc\migration\connection\PdoDriver;
use kuaukutsu\poc\migration\event\Event;
use kuaukutsu\poc\migration\tests\stub\TestSubscriber;
use kuaukutsu\poc\migration\tests\MigratorFactory;

final class EventTest extends TestCase
{
    public function testConfigurationError(): void
    {
        $eventSubscriber = new TestSubscriber();
        $migrator = MigratorFactory::makeFromEvent(
            new PdoDriver(
                dsn: 'test::memory:',
            ),
            [
                $eventSubscriber,
            ]
        );

        try {
            $migrator->init();
        } catch (Throwable) {
        }

        self::assertEquals(
            'PDODriver: is not implemented.',
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
            'PDO_MYSQL:SQLSTATE[HY000]',
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
            $eventSubscriber->get(Event::ConnectionError)
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
}
