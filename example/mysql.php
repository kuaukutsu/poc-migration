<?php

declare(strict_types=1);

use kuaukutsu\poc\migration\internal\connection\PDO\Driver;
use kuaukutsu\poc\migration\tools\PrettyConsoleOutput;
use kuaukutsu\poc\migration\InputArgs;
use kuaukutsu\poc\migration\Migration;
use kuaukutsu\poc\migration\MigrationCollection;
use kuaukutsu\poc\migration\Migrator;

require dirname(__DIR__) . '/vendor/autoload.php';

$migrator = new Migrator(
    collection: new MigrationCollection(
        new Migration(
            path: __DIR__ . '/migration/mysql/main',
            driver: new Driver(
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

foreach (range(1, 1000) as $row) {
    $migrator->create(new InputArgs(dbName: "mysql/main", migrationName: $row . "-test"));
}

try {
    $migrator->init();
} catch (Throwable $exception) {
    echo $exception->getMessage() . PHP_EOL;
}

try {
    $migrator->up(new InputArgs(limit: 100));
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

$pattern = __DIR__ . '/migration/mysql/main/*test.sql';
/** @psalm-suppress RiskyTruthyFalsyComparison */
foreach (glob($pattern) ?: [] as $file) {
    if (is_file($file)) {
        unlink($file);
    }
}
