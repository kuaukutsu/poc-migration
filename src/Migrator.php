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
        private MigrationCollection $dbCollection,
        array $eventSubscribers = [],
    ) {
        $this->eventDispatcher = new EventDispatcher($eventSubscribers);
        $this->actionWorkflow = new Workflow($this->eventDispatcher);
    }

    #[Override]
    public function init(): void
    {
        foreach ($this->dbCollection as $db) {
            $this->actionWorkflow->initialization($db);
        }
    }

    #[Override]
    public function up(InputArgs $args = new InputArgs()): void
    {
        foreach ($this->selectDb($args) as $db) {
            $this->actionWorkflow->up($db, $args);

            if ($args->hasRepeatable) {
                $this->actionWorkflow->repeatable($db, $args);
            }
        }
    }

    #[Override]
    public function down(InputArgs $args = new InputArgs()): void
    {
        foreach ($this->selectDb($args) as $db) {
            $this->actionWorkflow->down($db, $args);
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
        foreach ($this->selectDb($args) as $db) {
            $this->actionWorkflow->fixture($db, $args);
        }
    }

    /**
     * @return iterable<Migration>
     * @throws ConfigurationException
     */
    private function selectDb(InputArgs $args): iterable
    {
        if ($args->dbName === null) {
            return $this->dbCollection;
        }

        $db = $this->dbCollection->get($args->dbName);
        if ($db instanceof Migration) {
            return [$db];
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
