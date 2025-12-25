<?php

declare(strict_types=1);

use DI\Container;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use kuaukutsu\poc\migration\connection\PdoDriver;
use kuaukutsu\poc\migration\presentation\DownCommand;
use kuaukutsu\poc\migration\presentation\FixtureCommand;
use kuaukutsu\poc\migration\presentation\InitCommand;
use kuaukutsu\poc\migration\presentation\UpCommand;
use kuaukutsu\poc\migration\tools\PrettyConsoleOutput;
use kuaukutsu\poc\migration\Db;
use kuaukutsu\poc\migration\DbCollection;
use kuaukutsu\poc\migration\Migrator;
use kuaukutsu\poc\migration\MigratorInterface;

use function DI\factory;

require dirname(__DIR__) . '/vendor/autoload.php';

$container = new Container(
    [
        Migrator::class => factory(
            fn(): MigratorInterface => new Migrator(
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
    echo $e->getMessage() . PHP_EOL;
    exit(Command::FAILURE);
}
