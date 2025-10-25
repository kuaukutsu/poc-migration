<?php

declare(strict_types=1);

use Symfony\Component\Console\Output\ConsoleOutput;
use kuaukutsu\poc\migration\connection\PdoDriver;
use kuaukutsu\poc\migration\tools\TraceConsoleOutput;
use kuaukutsu\poc\migration\Db;
use kuaukutsu\poc\migration\DbCollection;
use kuaukutsu\poc\migration\Migrator;

require __DIR__ . '/bootstrap.php';

echo "example" . PHP_EOL;

$migrator = new Migrator(
    dbCollection: new DbCollection(
        new Db(
            path: __DIR__ . '/data/postgres/main',
            driver: new PdoDriver(
                dsn: 'pgsql:host=postgres;port=5432;dbname=main',
                username: 'postgres',
                password: 'postgres',
            )
        ),
        new Db(
            path: __DIR__ . '/data/mysql/main',
            driver: new PdoDriver(
                dsn: 'mysql:host=mysql;dbname=main',
                username: 'dbuser',
                password: 'dbpassword',
            )
        ),
    ),
    eventSubscribers: [
        new TraceConsoleOutput(new ConsoleOutput()),
    ],
);

try {
    $migrator->init();
} catch (Throwable $exception) {
    echo $exception->getMessage() . PHP_EOL;
}
