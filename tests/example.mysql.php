<?php

declare(strict_types=1);

use Symfony\Component\Console\Output\ConsoleOutput;
use kuaukutsu\poc\migration\connection\PdoDriver;
use kuaukutsu\poc\migration\Db;
use kuaukutsu\poc\migration\DbCollection;
use kuaukutsu\poc\migration\Migrator;
use kuaukutsu\poc\migration\tools\TraceConsoleOutput;

require dirname(__DIR__) . '/vendor/autoload.php';

$migrator = new Migrator(
    dbCollection: new DbCollection(
        new Db(
            path: __DIR__ . '/migration/mysql/main',
            driver: new PdoDriver(
                dsn: 'mysql:host=mysql;dbname=main',
                username: 'dbuser',
                password: 'dbpassword',
            )
        )
    ),
    eventSubscribers: [
        new TraceConsoleOutput(new ConsoleOutput()),
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
