<?php

declare(strict_types=1);

use kuaukutsu\poc\migration\Migration;
use kuaukutsu\poc\migration\MigrationCollection;
use kuaukutsu\poc\migration\driver\PdoDriver;
use kuaukutsu\poc\migration\Migrator;
use kuaukutsu\poc\migration\tools\PrettyConsoleOutput;

require dirname(__DIR__) . '/vendor/autoload.php';

$migrator = new Migrator(
    dbCollection: new MigrationCollection(
        new Migration(
            path: __DIR__ . '/migration/postgres/main',
            driver: new PdoDriver(
                dsn: 'pgsql:host=postgres;port=5432;dbname=main',
                username: 'postgres',
                password: 'postgres',
            )
        )
    ),
    eventSubscribers: [
        new PrettyConsoleOutput(),
    ],
);

try {
    $migrator->init();
} catch (Throwable $exception) {
    echo $exception->getMessage() . PHP_EOL;
}

try {
    $migrator->up();
} catch (Throwable $exception) {
    echo $exception->getMessage() . PHP_EOL;
}

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
