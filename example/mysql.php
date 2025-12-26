<?php

declare(strict_types=1);

use kuaukutsu\poc\migration\connection\PdoDriver;
use kuaukutsu\poc\migration\tools\PrettyConsoleOutput;
use kuaukutsu\poc\migration\Db;
use kuaukutsu\poc\migration\DbCollection;
use kuaukutsu\poc\migration\Migrator;

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
