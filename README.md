# Proof of Concept: Database Migrator

[![PHP Version Require](http://poser.pugx.org/kuaukutsu/poc-migration/require/php)](https://packagist.org/packages/kuaukutsu/poc-migration)
[![Latest Stable Version](https://poser.pugx.org/kuaukutsu/poc-migration/v/stable)](https://packagist.org/packages/kuaukutsu/poc-migration)
[![License](http://poser.pugx.org/kuaukutsu/poc-migration/license)](https://packagist.org/packages/kuaukutsu/poc-migration)
[![Psalm Level](https://shepherd.dev/github/kuaukutsu/poc-migration/level.svg)](https://shepherd.dev/github/kuaukutsu/poc-migration)
[![Psalm Type Coverage](https://shepherd.dev/github/kuaukutsu/poc-migration/coverage.svg)](https://shepherd.dev/github/kuaukutsu/poc-migration)

Консольная программа для управления миграциями.

### setup

Например, для базы данных с именем _main_ под управлением сервера **postgres**:
```shell
mkdir -p ./migration/pgsql/{main,main-fixture} 
```

Описываем конфигурацию:
```php
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
);
```

### migration

Команды миграции описываются на языке SQL, например:
```sql
-- @up
CREATE TABLE IF NOT EXISTS public.entity (
    id serial NOT NULL,
    parent_id integer NOT NULL,
    created_at timestamp(0) DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp(0) DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT entity_pkey PRIMARY KEY (id)
);
CREATE INDEX IF NOT EXISTS "I_entity_parent_id" ON public.entity USING btree (parent_id);

-- @down
DROP INDEX IF EXISTS I_entity_parent_id;
DROP TABLE IF EXISTS public.entity;
```

Управляющие команды:

- `@up`
- `@down`
- `@skip`

Если команды не указаны, то весь код будет вычитан как секция `up`.  
Если нужно скипнуть файл целиком, то можно добавить в название постфикс `skip`, например `202501011025_name_skip.sql`

### CLI application

```php
use DI\Container;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use kuaukutsu\poc\migration\connection\PdoDriver;
use kuaukutsu\poc\migration\tools\PrettyConsoleOutput;
use kuaukutsu\poc\migration\Db;
use kuaukutsu\poc\migration\DbCollection;
use kuaukutsu\poc\migration\Migrator;
use kuaukutsu\poc\migration\MigratorInterface;
use kuaukutsu\poc\migration\example\presentation\DownCommand;
use kuaukutsu\poc\migration\example\presentation\FixtureCommand;
use kuaukutsu\poc\migration\example\presentation\InitCommand;
use kuaukutsu\poc\migration\example\presentation\UpCommand;

require dirname(__DIR__) . '/vendor/autoload.php';

$container = new Container(
    [
        Migrator::class => factory(
            fn(): Migrator => new Migrator(
                dbCollection: new DbCollection(
                    new Db(
                        path: __DIR__ . '/migration/sqlite/memory',
                        driver: new PdoDriver(
                            dsn: 'sqlite:' . __DIR__ . '/data/sqlite/db.sqlite3',
                        )
                    )
                ),
                eventSubscribers: [
                    new PrettyConsoleOutput(),
                ],
            )
        ),
    ]
);

$console = new Application();
$console->setCommandLoader(
    new ContainerCommandLoader(
        $container,
        [
            'migrate:init' => InitCommand::class,
            'migrate:up' => UpCommand::class,
            'migrate:down' => DownCommand::class,
            'migrate:fixture' => FixtureCommand::class,
        ],
    )
);

try {
    exit($console->run());
} catch (Exception $e) {
    exit(Command::FAILURE);
}
```

### Example

```shell
make app
```

```shell
/example $ php cli.php migrate:init
[sqlite/memory] initialization: setup.sql done

/example $ php cli.php migrate:up
[sqlite/memory] up: 202501011024_entity_create.sql done
[sqlite/memory] up: 202501021024_account_create.sql done
[sqlite/memory] up: 202501021025_account_email.sql done
[sqlite/memory] repeatable: 202501011024_entity_correction.sql done
[sqlite/memory] repeatable: 202501011024_entity_correction_2.sql done

/example $ php cli.php migrate:down
[sqlite/memory] down: 202501021025_account_email.sql done
[sqlite/memory] down: 202501021024_account_create.sql done
[sqlite/memory] down: 202501011024_entity_create.sql done
```

### Static analysis

To run static analysis:

```shell
make psalm
```

```shell
make phpstan
```

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
make phpunit
```
