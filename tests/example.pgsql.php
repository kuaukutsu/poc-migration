<?php

declare(strict_types=1);

use League\CLImate\CLImate;
use kuaukutsu\poc\migration\connection\PdoDriver;
use kuaukutsu\poc\migration\Db;
use kuaukutsu\poc\migration\DbCollection;
use kuaukutsu\poc\migration\Migrator;
use kuaukutsu\poc\migration\tools\PrettyConsoleOutput;

require dirname(__DIR__) . '/vendor/autoload.php';

$migrator = new Migrator(
    dbCollection: new DbCollection(
        new Db(
            path: __DIR__ . '/migration/postgres/main',
            driver: new PdoDriver(
                dsn: 'pgsql:host=postgres;port=5432;dbname=main',
                username: 'postgres',
                password: 'postgres',
            )
        )
    ),
    eventSubscribers: [
        new PrettyConsoleOutput(new CLImate()),
    ],
);

try {
    $migrator->up();
} catch (Throwable $exception) {
    echo $exception->getMessage() . PHP_EOL;
}

try {
    $migrator->down();
} catch (Throwable $exception) {
    echo $exception->getMessage() . PHP_EOL;
}
