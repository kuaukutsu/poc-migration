<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests;

use kuaukutsu\poc\migration\Migration;
use kuaukutsu\poc\migration\MigrationCollection;
use kuaukutsu\poc\migration\driver\DriverInterface;
use kuaukutsu\poc\migration\event\EventSubscriberInterface;
use kuaukutsu\poc\migration\Migrator;
use kuaukutsu\poc\migration\MigratorInterface;

final readonly class MigratorFactory
{
    private function __construct()
    {
    }

    public static function makeFromDriver(DriverInterface $driver): MigratorInterface
    {
        return new Migrator(
            collection: new MigrationCollection(
                new Migration(
                    path: __DIR__ . '/migration/sqlite/memory',
                    driver: $driver
                ),
            ),
        );
    }

    /**
     * @param list<EventSubscriberInterface> $eventSubscribers
     */
    public static function makeFromEvent(DriverInterface $driver, array $eventSubscribers = []): MigratorInterface
    {
        return new Migrator(
            collection: new MigrationCollection(
                new Migration(
                    path: __DIR__ . '/migration/sqlite/event',
                    driver: $driver
                ),
            ),
            eventSubscribers: $eventSubscribers,
        );
    }
}
