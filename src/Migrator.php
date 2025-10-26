<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration;

use kuaukutsu\poc\migration\connection\Command;
use kuaukutsu\poc\migration\connection\Params;
use kuaukutsu\poc\migration\event\ConfigurationErrorEvent;
use kuaukutsu\poc\migration\event\ConnectionErrorEvent;
use kuaukutsu\poc\migration\event\EventDispatcher;
use kuaukutsu\poc\migration\event\EventSubscriberInterface;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\exception\ConnectionException;
use kuaukutsu\poc\migration\exception\InitializationException;
use kuaukutsu\poc\migration\internal\ActionWorkflow;
use kuaukutsu\poc\migration\internal\MigrateArgs;

/**
 * @api
 */
final readonly class Migrator
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

    /**
     * @throws ConfigurationException If the driver is not implemented
     * @throws ConnectionException
     */
    public function init(): void
    {
        foreach ($this->dbCollection as $db) {
            $this->actionWorkflow->initialization($db, $this->makeCommand($db));
        }
    }

    /**
     * @throws ConfigurationException If the driver is not implemented
     * @throws ConnectionException
     * @throws InitializationException
     */
    public function up(MigrateArgs $args = new MigrateArgs()): void
    {
        foreach ($this->dbCollection as $db) {
            $command = $this->makeCommand($db);
            $this->actionWorkflow->up($db, $command, $args);
            $this->actionWorkflow->repeatable($db, $command, $args);
        }
    }

    /**
     * @throws ConfigurationException If the driver is not implemented
     * @throws ConnectionException
     * @throws InitializationException
     */
    public function down(MigrateArgs $args = new MigrateArgs()): void
    {
        foreach ($this->dbCollection as $db) {
            $this->actionWorkflow->down($db, $this->makeCommand($db), $args);
        }
    }

    /**
     * @throws ConfigurationException If the driver is not implemented
     * @throws ConnectionException
     */
    public function fixture(MigrateArgs $args = new MigrateArgs()): void
    {
        foreach ($this->dbCollection as $db) {
            $this->actionWorkflow->fixture($db, $this->makeCommand($db), $args);
        }
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
                new ConnectionErrorEvent($db->getName(), $exception)
            );

            throw $exception;
        } catch (ConfigurationException $exception) {
            $this->eventDispatcher->trigger(
                new ConfigurationErrorEvent($db->getName(), $exception)
            );

            throw $exception;
        }
    }
}
