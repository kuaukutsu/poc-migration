<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\action;

use Iterator;
use Throwable;
use DateTimeImmutable;
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

        $appliedMigrations = $this->getAppliedMigrations($migration, $command, new command\Args());
        $fsHandler = static fn(filesystem\Action $fs): Iterator => $fs->up(
            $appliedMigrations,
            filesystem\Args::makeFromInput($args)
        );

        $version = generateVersion();
        foreach ($this->iteratorHandler($migration, $fsHandler) as $filename => $query) {
            $this->run(
                $command->up(...),
                new Context(
                    dbName: $migration->getName(),
                    filename: $filename,
                    query: $query,
                    version: $version,
                    dryRun: $args->dryRun,
                ),
                EventAction::up,
            );
        }

        if ($args->hasRepeatable()) {
            $this->repeatable($migration, $command, $version);
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

        $appliedMigrations = $this->getAppliedMigrations($migration, $command, command\Args::makeFromInput($args));
        $fsHandler = static fn(filesystem\Action $fs): Iterator => $fs->down($appliedMigrations);

        foreach ($this->iteratorHandler($migration, $fsHandler) as $filename => $query) {
            $this->run(
                $command->down(...),
                new Context(
                    dbName: $migration->getName(),
                    filename: $filename,
                    query: $query,
                    version: $appliedMigrations[$filename],
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

        foreach ($this->iteratorHandler($migration, $fsHandler) as $filename => $query) {
            $this->run(
                $command->exec(...),
                new Context(
                    dbName: $migration->getName(),
                    filename: $filename,
                    query: $query,
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

        foreach ($files as $filename => $query) {
            $this->run(
                $command->exec(...),
                new Context(
                    dbName: $migration->getName(),
                    filename: $filename,
                    query: $query,
                ),
                EventAction::initialization,
            );
        }
    }

    /**
     * @param non-negative-int $version
     * @throws ActionException
     * @throws ConfigurationException
     * @throws ConnectionException
     */
    private function repeatable(Migration $migration, CommandInterface $command, int $version): void
    {
        $fsHandler = static fn(filesystem\Action $fs): Iterator => $fs->repeatable();

        foreach ($this->iteratorHandler($migration, $fsHandler, false) as $filename => $query) {
            $this->run(
                $command->exec(...),
                new Context(
                    dbName: $migration->getName(),
                    filename: $filename,
                    query: $query,
                    version: $version,
                ),
                EventAction::repeatable,
            );
        }
    }

    /**
     * @return array<non-empty-string, non-negative-int>
     * @throws InitializationException
     */
    private function getAppliedMigrations(Migration $migration, CommandInterface $command, command\Args $args): array
    {
        try {
            if ($args->applyLatestVersion) {
                $appliedMigrations = $command->fetchApplied(new command\Args(limit: 1));
                if (count($appliedMigrations) === 1) {
                    $args = $args->withVersion(current($appliedMigrations));
                }
            }

            return $command->fetchApplied($args);
        } catch (Throwable $exception) {
            $this->eventDispatcher->trigger(
                Event::InitializationError,
                new ExceptionEvent($migration->getName(), $exception)
            );

            throw new InitializationException('Error reading system data.', $exception);
        }
    }

    /**
     * @param callable(Context $context):bool $handler
     * @throws ActionException
     */
    private function run(callable $handler, Context $context, EventAction $action): void
    {
        try {
            $handler($context);
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

/**
 * Unixtime + milleseconds
 * @return positive-int
 */
function generateVersion(): int
{
    /**
     * @var positive-int
     */
    return (int)substr((new DateTimeImmutable())->format('Uv'), 0, -1);
}
