<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal;

use Throwable;
use kuaukutsu\poc\migration\connection\Command;
use kuaukutsu\poc\migration\connection\CommandArgs;
use kuaukutsu\poc\migration\event\EventAction;
use kuaukutsu\poc\migration\event\EventDispatcher;
use kuaukutsu\poc\migration\event\FilesystemErrorEvent;
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
            throw new InitializationException('Error reading system data.', $exception);
        }

        $fs = new ActionFilesystem($db->path);
        try {
            $files = $fs->up($savedMigration, FilesystemArgs::makeFromMigrateArgs($args));
        } catch (ConfigurationException $exception) {
            $this->eventDispatcher->trigger(
                new FilesystemErrorEvent($db->path, $exception)
            );

            throw $exception;
        }

        foreach ($files as $filename => $queryString) {
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
            throw new InitializationException('Error reading system data.', $exception);
        }

        $fs = new ActionFilesystem($db->path);
        try {
            $files = $fs->down($savedMigration);
        } catch (ConfigurationException $exception) {
            $this->eventDispatcher->trigger(
                new FilesystemErrorEvent($db->path, $exception)
            );

            throw $exception;
        }

        foreach ($files as $filename => $queryString) {
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
        $fs = new ActionFilesystem($db->path);
        try {
            $files = $fs->fixture(FilesystemArgs::makeFromMigrateArgs($args));
        } catch (ConfigurationException $exception) {
            $this->eventDispatcher->trigger(
                new FilesystemErrorEvent($db->path, $exception)
            );

            throw $exception;
        }

        foreach ($files as $filename => $queryString) {
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
        $fs = new ActionFilesystem($db->path);
        try {
            $files = $fs->repeatable();
        } catch (ConfigurationException $exception) {
            $this->eventDispatcher->trigger(
                new FilesystemErrorEvent($db->path, $exception)
            );

            // для repeatable допускатся отсутствие целевого каталога.
            return;
        }


        foreach ($files as $filename => $queryString) {
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
        $fs = new SetupFilesystem($db->getSetupFilepath(), $db->table);
        try {
            $files = $fs->all();
        } catch (ConfigurationException $exception) {
            $this->eventDispatcher->trigger(
                new FilesystemErrorEvent($db->path, $exception)
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
                new MigrateSuccessEvent($action->name, $context)
            );
        } catch (Throwable $exception) {
            $this->eventDispatcher->trigger(
                new MigrateErrorEvent($action->name, $context, $exception)
            );

            throw new MigrationException($context->filename, $exception);
        }
    }
}
