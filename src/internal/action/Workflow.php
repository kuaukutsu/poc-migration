<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\action;

use Throwable;
use DateTimeImmutable;
use Iterator;
use kuaukutsu\poc\migration\command\CommandInterface;
use kuaukutsu\poc\migration\command\Options;
use kuaukutsu\poc\migration\event\Event;
use kuaukutsu\poc\migration\event\EventAction;
use kuaukutsu\poc\migration\event\EventDispatcher;
use kuaukutsu\poc\migration\event\ExceptionEvent;
use kuaukutsu\poc\migration\event\MigrateErrorEvent;
use kuaukutsu\poc\migration\event\MigrateSuccessEvent;
use kuaukutsu\poc\migration\exception\ActionException;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\exception\ConnectionException;
use kuaukutsu\poc\migration\exception\InitializationException;
use kuaukutsu\poc\migration\internal\filesystem;
use kuaukutsu\poc\migration\Context;
use kuaukutsu\poc\migration\InputOptions;
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
     * @return non-negative-int Version current transaction
     * @throws ActionException
     * @throws ConfigurationException If the driver is not implemented
     * @throws ConnectionException
     * @throws InitializationException
     */
    public function up(Migration $migration, InputOptions $options): int
    {
        $command = $this->makeCommand($migration);

        $appliedMigrations = $this->getAppliedMigrations($migration, $command, new Options());
        $fsHandler = static fn(filesystem\Action $fs): Iterator => $fs->up(
            $appliedMigrations,
            filesystem\Options::makeFromInput($options)
        );

        $version = generateVersion();
        foreach ($this->iteratorHandler($migration, $fsHandler) as $filename => $query) {
            try {
                $this->run(
                    $command->up(...),
                    new Context(
                        dbName: $migration->getName(),
                        filename: $filename,
                        query: $query,
                        version: $version,
                        dryRun: $options->dryRun,
                    ),
                    EventAction::up,
                );
            } catch (ActionException $exception) {
                if ($options->exactlyAll) {
                    $this->down($migration, new InputOptions(version: $version));
                }

                throw $exception;
            }
        }

        if ($options->hasRepeatable()) {
            $this->repeatable($migration, $command, $version);
        }

        return $version;
    }

    /**
     * @throws ActionException
     * @throws ConfigurationException If the driver is not implemented
     * @throws ConnectionException
     * @throws InitializationException
     */
    public function down(Migration $migration, InputOptions $options): void
    {
        $command = $this->makeCommand($migration);

        $commandOptions = Options::makeFromInput($options);
        if ($options->hasApplyLatestVersion()) {
            $commandOptions = $commandOptions->withVersion(
                $this->getLastVersion($migration, $command)
            );
        }

        $appliedMigrations = $this->getAppliedMigrations($migration, $command, $commandOptions);
        $fsHandler = static fn(filesystem\Action $fs): Iterator => $fs->down($appliedMigrations);

        foreach ($this->iteratorHandler($migration, $fsHandler) as $filename => $query) {
            $this->run(
                $command->down(...),
                new Context(
                    dbName: $migration->getName(),
                    filename: $filename,
                    query: $query,
                    version: $appliedMigrations[$filename],
                    dryRun: $options->dryRun,
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
    public function fixture(Migration $migration, InputOptions $options): void
    {
        $command = $this->makeCommand($migration);

        $fsHandler = static fn(filesystem\Action $fs): Iterator => $fs->fixture(
            filesystem\Options::makeFromInput($options)
        );

        foreach ($this->iteratorHandler($migration, $fsHandler) as $filename => $query) {
            $this->run(
                $command->exec(...),
                new Context(
                    dbName: $migration->getName(),
                    filename: $filename,
                    query: $query,
                    dryRun: $options->dryRun,
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
            $files = (new filesystem\Setup($migration->getSetupPath(), $migration->config->table))->all();
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
     * @param non-empty-string $name
     * @throws ConfigurationException
     */
    public function create(Migration $migration, string $name): void
    {
        try {
            (new filesystem\Action($migration->path))->create(
                $migration->config->templFactory->makeName($name),
                $migration->config->templFactory->makeBody(),
            );
        } catch (ConfigurationException $exception) {
            $this->eventDispatcher->trigger(
                Event::FilesystemError,
                new ExceptionEvent($migration->getName(), $exception)
            );

            throw $exception;
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
    private function getAppliedMigrations(Migration $migration, CommandInterface $command, Options $options): array
    {
        try {
            return $command->fetchApplied($options);
        } catch (Throwable $exception) {
            $this->eventDispatcher->trigger(
                Event::InitializationError,
                new ExceptionEvent($migration->getName(), $exception)
            );

            throw new InitializationException('Error reading system data.', $exception);
        }
    }

    /**
     * @return non-negative-int
     * @throws InitializationException
     */
    private function getLastVersion(Migration $migration, CommandInterface $command): int
    {
        $appliedMigrations = $this->getAppliedMigrations($migration, $command, new Options(limit: 1));
        if (count($appliedMigrations) === 1) {
            return current($appliedMigrations);
        }

        return 0;
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
 * Unixtime + (milleseconds - 1 char), немного усекаем строку до 12 знаков.
 * @return positive-int
 */
function generateVersion(): int
{
    /**
     * @var positive-int
     */
    return (int)substr((new DateTimeImmutable())->format('Uv'), 0, 12);
}
