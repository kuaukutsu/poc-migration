<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration;

use Override;
use kuaukutsu\poc\migration\connection\Command;
use kuaukutsu\poc\migration\connection\Params;
use kuaukutsu\poc\migration\event\ConfigurationEvent;
use kuaukutsu\poc\migration\event\Event;
use kuaukutsu\poc\migration\event\EventDispatcher;
use kuaukutsu\poc\migration\event\EventSubscriberInterface;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\exception\ConnectionException;
use kuaukutsu\poc\migration\internal\ActionWorkflow;

/**
 * @api
 */
final readonly class Migrator implements MigratorInterface
{
    private ActionWorkflow $actionWorkflow;

    private EventDispatcher $eventDispatcher;

    /**
     * @param list<EventSubscriberInterface> $eventSubscribers
     */
    public function __construct(
        private DbCollection $dbCollection,
        array $eventSubscribers = [],
    ) {
        $this->eventDispatcher = new EventDispatcher($eventSubscribers);
        $this->actionWorkflow = new ActionWorkflow($this->eventDispatcher);
    }

    #[Override]
    public function init(): void
    {
        foreach ($this->dbCollection as $db) {
            $this->actionWorkflow->initialization($db, $this->makeCommand($db));
        }
    }

    #[Override]
    public function up(MigratorArgs $args = new MigratorArgs()): void
    {
        foreach ($this->selectDb($args) as $db) {
            $command = $this->makeCommand($db);
            $this->actionWorkflow->up($db, $command, $args);

            if ($args->hasRepeatable) {
                $this->actionWorkflow->repeatable($db, $command, $args);
            }
        }
    }

    #[Override]
    public function down(MigratorArgs $args = new MigratorArgs()): void
    {
        foreach ($this->selectDb($args) as $db) {
            $this->actionWorkflow->down($db, $this->makeCommand($db), $args);
        }
    }

    #[Override]
    public function redo(MigratorArgs $args = new MigratorArgs()): void
    {
        $this->down($args);
        $this->up($args->withResetLimit());
    }

    #[Override]
    public function fixture(MigratorArgs $args = new MigratorArgs()): void
    {
        foreach ($this->selectDb($args) as $db) {
            $this->actionWorkflow->fixture($db, $this->makeCommand($db), $args);
        }
    }

    /**
     * @return iterable<Db>
     * @throws ConfigurationException
     */
    private function selectDb(MigratorArgs $args): iterable
    {
        if ($args->dbName === null) {
            return $this->dbCollection;
        }

        $db = $this->dbCollection->get($args->dbName);
        if ($db instanceof Db) {
            return [$db];
        }

        $exception = new ConfigurationException(
            "[$args->dbName] no such database in the configuration."
        );

        $this->eventDispatcher->trigger(
            Event::ConfigurationError,
            new ConfigurationEvent($args->dbName, $exception)
        );

        throw $exception;
    }

    /**
     * @throws ConfigurationException
     * @throws ConnectionException
     */
    private function makeCommand(Db $db): Command
    {
        try {
            return $db->driver->makeCommand(
                new Params(table: $db->table)
            );
        } catch (ConnectionException $exception) {
            $this->eventDispatcher->trigger(
                Event::ConnectionError,
                new ConfigurationEvent($db->getName(), $exception)
            );

            throw $exception;
        } catch (ConfigurationException $exception) {
            $this->eventDispatcher->trigger(
                Event::ConfigurationError,
                new ConfigurationEvent($db->getName(), $exception)
            );

            throw $exception;
        }
    }
}
