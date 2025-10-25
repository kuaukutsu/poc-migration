# Proof of Concept: Database Migrator

draft...

## example

```php
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
    $migrator->up();
} catch (Throwable $exception) {
    echo $exception->getMessage() . PHP_EOL;
}

try {
    $migrator->down();
} catch (Throwable $exception) {
    echo $exception->getMessage() . PHP_EOL;
}
```
