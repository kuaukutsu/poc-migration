<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\action;

use Iterator;
use Throwable;
use kuaukutsu\poc\migration\event\ExceptionEvent;
use kuaukutsu\poc\migration\event\Event;
use kuaukutsu\poc\migration\event\EventAction;
use kuaukutsu\poc\migration\event\EventDispatcher;
use kuaukutsu\poc\migration\event\MigrateErrorEvent;
use kuaukutsu\poc\migration\event\MigrateSuccessEvent;
use kuaukutsu\poc\migration\exception\ActionException;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\exception\ConnectionException;
use kuaukutsu\poc\migration\exception\InitializationException;
use kuaukutsu\poc\migration\internal\command;
use kuaukutsu\poc\migration\internal\command\CommandInterface;
use kuaukutsu\poc\migration\internal\filesystem;
use kuaukutsu\poc\migration\Context;
use kuaukutsu\poc\migration\InputArgs;
use kuaukutsu\poc\migration\Migration;

/**
 * @psalm-internal kuaukutsu\poc\migration
 */
final readonly class Workflow
{
    public function __construct(private EventDispatcher $eventDispatcher)
    {
    }

    /**
     * @throws ActionException
     * @throws ConfigurationException If the driver is not implemented
     * @throws ConnectionException
     * @throws InitializationException
     */
    public function up(Migration $migration, InputArgs $args): void
    {
        $command = $this->makeCommand($migration);

        $savedMigration = $this->fetchSavedMigration($migration, $command, new command\Args());
        $fsHandler = static fn(filesystem\Action $fs): Iterator => $fs->up(
            $savedMigration,
            filesystem\Args::makeFromInput($args)
        );

        foreach ($this->iteratorHandler($migration, $fsHandler) as $filename => $queryString) {
            $this->run(
                $command->up(...),
                new Context(
                    dbName: $migration->getName(),
                    filename: $filename,
                    queryString: $queryString,
                    dryRun: $args->dryRun,
                ),
                EventAction::up,
            );
        }
    }

    /**
     * @throws ActionException
     * @throws ConfigurationException If the driver is not implemented
     * @throws ConnectionException
     * @throws InitializationException
     */
    public function down(Migration $migration, InputArgs $args): void
    {
        $command = $this->makeCommand($migration);

        $savedMigration = $this->fetchSavedMigration($migration, $command, command\Args::makeFromInput($args));
        $fsHandler = static fn(filesystem\Action $fs): Iterator => $fs->down($savedMigration);

        foreach ($this->iteratorHandler($migration, $fsHandler) as $filename => $queryString) {
            $this->run(
                $command->down(...),
                new Context(
                    dbName: $migration->getName(),
                    filename: $filename,
                    queryString: $queryString,
                    dryRun: $args->dryRun,
                ),
                EventAction::down,
            );
        }
    }

    /**
     * @throws ActionException
     * @throws ConfigurationException
     * @throws ConnectionException
     */
    public function fixture(Migration $migration, InputArgs $args): void
    {
        $command = $this->makeCommand($migration);

        $fsHandler = static fn(filesystem\Action $fs): Iterator => $fs->fixture(
            filesystem\Args::makeFromInput($args)
        );

        foreach ($this->iteratorHandler($migration, $fsHandler) as $filename => $queryString) {
            $this->run(
                $command->exec(...),
                new Context(
                    dbName: $migration->getName(),
                    filename: $filename,
                    queryString: $queryString,
                    dryRun: $args->dryRun,
                ),
                EventAction::fixture,
            );
        }
    }

    /**
     * @throws ActionException
     * @throws ConfigurationException
     * @throws ConnectionException
     */
    public function repeatable(Migration $migration, InputArgs $args): void
    {
        $command = $this->makeCommand($migration);

        $fsHandler = static fn(filesystem\Action $fs): Iterator => $fs->repeatable();

        foreach ($this->iteratorHandler($migration, $fsHandler, false) as $filename => $queryString) {
            $this->run(
                $command->exec(...),
                new Context(
                    dbName: $migration->getName(),
                    filename: $filename,
                    queryString: $queryString,
                    dryRun: $args->dryRun,
                ),
                EventAction::repeatable,
            );
        }
    }

    /**
     * @throws ActionException
     * @throws ConfigurationException
     * @throws ConnectionException
     */
    public function initialization(Migration $migration): void
    {
        $command = $this->makeCommand($migration);

        try {
            $files = (new filesystem\Setup($migration->getSetupPath(), $migration->table))->all();
        } catch (ConfigurationException $exception) {
            $this->eventDispatcher->trigger(
                Event::FilesystemError,
                new ExceptionEvent($migration->getName(), $exception)
            );

            throw $exception;
        }

        foreach ($files as $filename => $queryString) {
            $this->run(
                $command->exec(...),
                new Context(
                    dbName: $migration->getName(),
                    filename: $filename,
                    queryString: $queryString,
                ),
                EventAction::initialization,
            );
        }
    }

    /**
     * @return list<non-empty-string>
     * @throws InitializationException
     */
    private function fetchSavedMigration(Migration $migration, CommandInterface $command, command\Args $args): array
    {
        try {
            return $command->fetchSavedMigrationNames($args);
        } catch (Throwable $exception) {
            $this->eventDispatcher->trigger(
                Event::InitializationError,
                new ExceptionEvent($migration->getName(), $exception)
            );

            throw new InitializationException('Error reading system data.', $exception);
        }
    }

    /**
     * @param callable(non-empty-string $queryString, non-empty-string $filename):bool $handler
     * @throws ActionException
     */
    private function run(callable $handler, Context $context, EventAction $action): void
    {
        if ($context->dryRun) {
            $this->eventDispatcher->trigger(
                Event::MigrateSuccess,
                new MigrateSuccessEvent($action->name, $context)
            );
            return;
        }

        try {
            $handler($context->queryString, $context->filename);
            $this->eventDispatcher->trigger(
                Event::MigrateSuccess,
                new MigrateSuccessEvent($action->name, $context)
            );
        } catch (Throwable $exception) {
            $this->eventDispatcher->trigger(
                Event::MigrateError,
                new MigrateErrorEvent($action->name, $context, $exception)
            );

            throw new ActionException($context->filename, $exception);
        }
    }

    /**
     * @param callable(filesystem\Action):Iterator<non-empty-string, non-empty-string> $handler
     * @return iterable<non-empty-string, non-empty-string>
     * @throws ConfigurationException
     */
    private function iteratorHandler(Migration $migration, callable $handler, bool $useException = true): iterable
    {
        try {
            $iterator = $handler(new filesystem\Action($migration->path));
            if ($iterator->valid() === false) {
                $this->eventDispatcher->trigger(
                    Event::FilesystemNotice,
                    new ExceptionEvent(
                        $migration->getName(),
                        new ConfigurationException(
                            sprintf('the directory [%s] does not contain migration files.', $migration->path)
                        )
                    )
                );

                return [];
            }
        } catch (ConfigurationException $exception) {
            $this->eventDispatcher->trigger(
                $useException ? Event::FilesystemError : Event::FilesystemNotice,
                new ExceptionEvent($migration->getName(), $exception)
            );

            if ($useException) {
                throw $exception;
            }

            return [];
        }

        return $iterator;
    }

    /**
     * @throws ConfigurationException
     * @throws ConnectionException
     */
    private function makeCommand(Migration $migration): CommandInterface
    {
        try {
            return $migration->getCommand();
        } catch (ConnectionException $exception) {
            $this->eventDispatcher->trigger(
                Event::ConnectionError,
                new ExceptionEvent($migration->getName(), $exception)
            );

            throw $exception;
        }
    }
}
