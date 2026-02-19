<?php

declare(strict_types=1);

use DI\Container;
use kuaukutsu\poc\migration\Migration;
use kuaukutsu\poc\migration\MigrationCollection;
use kuaukutsu\poc\migration\driver\PdoDriver;
use kuaukutsu\poc\migration\example\presentation\DownCommand;
use kuaukutsu\poc\migration\example\presentation\FixtureCommand;
use kuaukutsu\poc\migration\example\presentation\InitCommand;
use kuaukutsu\poc\migration\example\presentation\RedoCommand;
use kuaukutsu\poc\migration\example\presentation\UpCommand;
use kuaukutsu\poc\migration\Migrator;
use kuaukutsu\poc\migration\MigratorInterface;
use kuaukutsu\poc\migration\tools\PrettyConsoleOutput;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;

use function DI\factory;

require dirname(__DIR__) . '/vendor/autoload.php';

$container = new Container(
    [
        MigratorInterface::class => factory(
            fn(): MigratorInterface => new Migrator(
                dbCollection: new MigrationCollection(
                    new Migration(
                        path: __DIR__ . '/migration/sqlite/memory',
                        driver: new PdoDriver(
                            dsn: 'sqlite:' . __DIR__ . '/data/sqlite/db.sqlite3'
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
            'migrate:redo' => RedoCommand::class,
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
