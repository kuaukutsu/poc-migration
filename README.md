# Proof of Concept: Database Migrator

[![PHP Version Require](http://poser.pugx.org/kuaukutsu/poc-migration/require/php)](https://packagist.org/packages/kuaukutsu/poc-migration)
[![Latest Stable Version](https://poser.pugx.org/kuaukutsu/poc-migration/v/stable)](https://packagist.org/packages/kuaukutsu/poc-migration)
[![License](http://poser.pugx.org/kuaukutsu/poc-migration/license)](https://packagist.org/packages/kuaukutsu/poc-migration)
[![Psalm Level](https://shepherd.dev/github/kuaukutsu/poc-migration/level.svg)](https://shepherd.dev/github/kuaukutsu/poc-migration)
[![Psalm Type Coverage](https://shepherd.dev/github/kuaukutsu/poc-migration/coverage.svg)](https://shepherd.dev/github/kuaukutsu/poc-migration)

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
