<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal;

use Iterator;
use Throwable;
use kuaukutsu\poc\migration\connection\Command;
use kuaukutsu\poc\migration\connection\CommandArgs;
use kuaukutsu\poc\migration\event\ConfigurationEvent;
use kuaukutsu\poc\migration\event\Event;
use kuaukutsu\poc\migration\event\EventAction;
use kuaukutsu\poc\migration\event\EventDispatcher;
use kuaukutsu\poc\migration\event\MigrateErrorEvent;
use kuaukutsu\poc\migration\event\MigrateSuccessEvent;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\exception\ConnectionException;
use kuaukutsu\poc\migration\exception\InitializationException;
use kuaukutsu\poc\migration\exception\MigrationException;
use kuaukutsu\poc\migration\Db;

/**
 * @psalm-internal kuaukutsu\poc\migration
 */
final readonly class ActionWorkflow
{
    public function __construct(private EventDispatcher $eventDispatcher)
    {
    }

    /**
     * @throws ConfigurationException If the driver is not implemented
     * @throws ConnectionException
     * @throws InitializationException
     */
    public function up(Db $db, Command $command, MigrateArgs $args): void
    {
        try {
            $savedMigration = $command->fetchSavedMigrationNames();
        } catch (Throwable $exception) {
            $this->eventDispatcher->trigger(
                Event::InitializationError,
                new ConfigurationEvent($db, $exception)
            );

            throw new InitializationException('Error reading system data.', $exception);
        }

        $fsHandler = static fn(ActionFilesystem $fs): Iterator => $fs->up(
            $savedMigration,
            FilesystemArgs::makeFromMigrateArgs($args)
        );
        foreach ($this->iteratorHandler($db, $fsHandler) as $filename => $queryString) {
            $this->handler(
                $command->up(...),
                new MigrateContext($db->getName(), $filename, $queryString),
                EventAction::up,
            );
        }
    }

    /**
     * @throws ConfigurationException If the driver is not implemented
     * @throws ConnectionException
     * @throws InitializationException
     */
    public function down(Db $db, Command $command, MigrateArgs $args): void
    {
        try {
            $savedMigration = $command->fetchSavedMigrationNames(
                CommandArgs::makeFromMigrateArgs($args)
            );
        } catch (Throwable $exception) {
            $this->eventDispatcher->trigger(
                Event::InitializationError,
                new ConfigurationEvent($db, $exception)
            );

            throw new InitializationException('Error reading system data.', $exception);
        }

        $fsHandler = static fn(ActionFilesystem $fs): Iterator => $fs->down(
            $savedMigration
        );
        foreach ($this->iteratorHandler($db, $fsHandler) as $filename => $queryString) {
            $this->handler(
                $command->down(...),
                new MigrateContext($db->getName(), $filename, $queryString),
                EventAction::down,
            );
        }
    }

    /**
     * @throws ConfigurationException
     * @throws ConnectionException
     */
    public function fixture(Db $db, Command $command, MigrateArgs $args): void
    {
        $fsHandler = static fn(ActionFilesystem $fs): Iterator => $fs->fixture(
            FilesystemArgs::makeFromMigrateArgs($args)
        );
        foreach ($this->iteratorHandler($db, $fsHandler) as $filename => $queryString) {
            $this->handler(
                $command->exec(...),
                new MigrateContext($db->getName(), $filename, $queryString),
                EventAction::fixture,
            );
        }
    }

    /**
     * @throws ConfigurationException
     * @throws ConnectionException
     * @noinspection PhpUnusedParameterInspection
     */
    public function repeatable(Db $db, Command $command, MigrateArgs $args): void
    {
        $fsHandler = static fn(ActionFilesystem $fs): Iterator => $fs->repeatable();
        foreach ($this->iteratorHandler($db, $fsHandler, false) as $filename => $queryString) {
            $this->handler(
                $command->exec(...),
                new MigrateContext($db->getName(), $filename, $queryString),
                EventAction::repeatable,
            );
        }
    }

    /**
     * @throws ConfigurationException
     * @throws ConnectionException
     */
    public function initialization(Db $db, Command $command): void
    {
        try {
            $files = SetupFilesystem::make($db)->all();
        } catch (ConfigurationException $exception) {
            $this->eventDispatcher->trigger(
                Event::FilesystemError,
                new ConfigurationEvent($db, $exception)
            );

            throw $exception;
        }

        foreach ($files as $filename => $queryString) {
            $this->handler(
                $command->exec(...),
                new MigrateContext($db->getName(), $filename, $queryString),
                EventAction::initialization,
            );
        }
    }

    /**
     * @param callable(non-empty-string $queryString, non-empty-string $filename):bool $handler
     * @throws MigrationException
     */
    private function handler(callable $handler, MigrateContext $context, EventAction $action): void
    {
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

            throw new MigrationException($context->filename, $exception);
        }
    }

    /**
     * @param callable(ActionFilesystem):Iterator<non-empty-string, non-empty-string> $handler
     * @return iterable<non-empty-string, non-empty-string>
     * @throws ConfigurationException
     */
    private function iteratorHandler(Db $db, callable $handler, bool $useException = true): iterable
    {
        try {
            $iterator = $handler(new ActionFilesystem($db->path));
            if ($iterator->valid() === false) {
                $this->eventDispatcher->trigger(
                    Event::FilesystemNotice,
                    new ConfigurationEvent(
                        $db,
                        new ConfigurationException(
                            sprintf('the directory [%s] does not contain migration files.', $db->path)
                        )
                    )
                );

                return [];
            }
        } catch (ConfigurationException $exception) {
            $this->eventDispatcher->trigger(
                $useException ? Event::FilesystemError : Event::FilesystemNotice,
                new ConfigurationEvent($db, $exception)
            );

            if ($useException) {
                throw $exception;
            }

            return [];
        }

        return $iterator;
    }
}
