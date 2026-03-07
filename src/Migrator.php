<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration;

use Override;
use kuaukutsu\ds\collection\CollectionOutOfRangeException;
use kuaukutsu\poc\migration\event\ExceptionEvent;
use kuaukutsu\poc\migration\event\Event;
use kuaukutsu\poc\migration\event\EventDispatcher;
use kuaukutsu\poc\migration\event\EventSubscriberInterface;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\internal\action\Workflow;

/**
 * @api
 */
final readonly class Migrator implements MigratorInterface
{
    private Workflow $actionWorkflow;

    private EventDispatcher $eventDispatcher;

    /**
     * @param list<EventSubscriberInterface> $eventSubscribers
     */
    public function __construct(
        private MigrationCollection $collection,
        array $eventSubscribers = [],
    ) {
        $this->eventDispatcher = new EventDispatcher($eventSubscribers);
        $this->actionWorkflow = new Workflow($this->eventDispatcher);
    }

    #[Override]
    public function init(): void
    {
        foreach ($this->collection as $migration) {
            $this->actionWorkflow->initialization($migration);
        }
    }

    #[Override]
    public function up(InputArgs $args = new InputArgs()): void
    {
        foreach ($this->selectDb($args) as $migration) {
            $this->actionWorkflow->up($migration, $args);
        }
    }

    #[Override]
    public function down(InputArgs $args = new InputArgs()): void
    {
        foreach ($this->selectDb($args) as $migration) {
            $this->actionWorkflow->down($migration, $args);
        }
    }

    #[Override]
    public function redo(InputArgs $args = new InputArgs()): void
    {
        $this->down($args);
        $this->up($args->withResetLimit());
    }

    #[Override]
    public function fixture(InputArgs $args = new InputArgs()): void
    {
        foreach ($this->selectDb($args) as $migration) {
            $this->actionWorkflow->fixture($migration, $args);
        }
    }

    #[Override]
    public function create(InputArgs $args = new InputArgs()): void
    {
        if ($args->dbName === null) {
            throw new ConfigurationException(
                "DBName must be declared."
            );
        }

        if ($args->migrationName === null) {
            throw new ConfigurationException(
                "Migration Name must be declared."
            );
        }

        foreach ($this->selectDb($args) as $migration) {
            $this->actionWorkflow->create($migration, $args->migrationName);
        }
    }

    /**
     * @return iterable<Migration>
     * @throws ConfigurationException
     */
    private function selectDb(InputArgs $args): iterable
    {
        if ($args->dbName === null) {
            return $this->collection;
        }

        try {
            $migration = $this->collection->get($args->dbName);
        } catch (CollectionOutOfRangeException) {
            $migration = null;
        }

        if ($migration instanceof Migration) {
            return [$migration];
        }

        $exception = new ConfigurationException(
            "[$args->dbName] no such database in the configuration."
        );

        $this->eventDispatcher->trigger(
            Event::ConfigurationError,
            new ExceptionEvent($args->dbName, $exception)
        );

        throw $exception;
    }
}
