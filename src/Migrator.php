<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration;

use Override;
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

            if ($args->hasRepeatable) {
                $this->actionWorkflow->repeatable($migration, $args);
            }
        }
    }

    #[Override]
    public function down(InputArgs $args = new InputArgs()): void
    {
        foreach ($this->selectDb($args) as $migration) {
            $this->actionWorkflow->down($migration, $args);
        }
    }

    /**
     * @infection-ignore-all
     */
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

    /**
     * @return iterable<Migration>
     * @throws ConfigurationException
     */
    private function selectDb(InputArgs $args): iterable
    {
        if ($args->dbName === null) {
            return $this->collection;
        }

        $migration = $this->collection->get($args->dbName);
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
