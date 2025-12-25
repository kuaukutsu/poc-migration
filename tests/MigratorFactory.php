<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests;

use kuaukutsu\poc\migration\connection\Driver;
use kuaukutsu\poc\migration\event\EventSubscriberInterface;
use kuaukutsu\poc\migration\Db;
use kuaukutsu\poc\migration\DbCollection;
use kuaukutsu\poc\migration\Migrator;
use kuaukutsu\poc\migration\MigratorInterface;

final readonly class MigratorFactory
{
    private function __construct()
    {
    }

    public static function makeFromDriver(Driver $driver): MigratorInterface
    {
        return new Migrator(
            dbCollection: new DbCollection(
                new Db(
                    path: __DIR__ . '/migration/sqlite/memory',
                    driver: $driver
                ),
            ),
        );
    }

    /**
     * @param list<EventSubscriberInterface> $eventSubscribers
     */
    public static function makeFromEvent(Driver $driver, array $eventSubscribers = []): MigratorInterface
    {
        return new Migrator(
            dbCollection: new DbCollection(
                new Db(
                    path: __DIR__ . '/migration/sqlite/event',
                    driver: $driver
                ),
            ),
            eventSubscribers: $eventSubscribers,
        );
    }
}
