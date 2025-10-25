<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests;

use kuaukutsu\poc\migration\connection\Driver;
use kuaukutsu\poc\migration\Db;
use kuaukutsu\poc\migration\DbCollection;
use kuaukutsu\poc\migration\Migrator;

final readonly class MigratorFactory
{
    private function __construct()
    {
    }

    public static function makeFromDriver(Driver $driver): Migrator
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
}
