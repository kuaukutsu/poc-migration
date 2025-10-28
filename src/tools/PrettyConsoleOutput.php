<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tools;

use Override;
use League\CLImate\CLImate;
use kuaukutsu\poc\migration\event\Event;
use kuaukutsu\poc\migration\event\EventInterface;
use kuaukutsu\poc\migration\event\EventSubscriberInterface;
use kuaukutsu\poc\migration\event\MigrateErrorEvent;
use kuaukutsu\poc\migration\event\MigrateSuccessEvent;

/**
 * @see https://climate.thephpleague.com/
 */
final readonly class PrettyConsoleOutput implements EventSubscriberInterface
{
    public function __construct(
        private CLImate $output = new CLImate(),
    ) {
    }

    #[Override]
    public function subscriptions(): array
    {
        $subscriptions = [];
        foreach (Event::cases() as $event) {
            $subscriptions[$event->value] = match ($event) {
                Event::MigrateSuccess => $this->success(...),
                Event::MigrateError => $this->errorMigration(...),
                Event::FilesystemNotice => $this->notice(...),
                default => $this->error(...),
            };
        }

        /**
         * @var non-empty-array<string, callable(Event $name, EventInterface $event):void> $subscriptions
         * @phpstan-ignore varTag.nativeType
         */
        return $subscriptions;
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function success(Event $name, MigrateSuccessEvent $event): void
    {
        $this->output->out(
            sprintf(
                '[<bold>%s</bold>] %s: %s <green>done</green>',
                $event->context->dbName,
                $event->action,
                $event->context->filename,
            )
        );
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function errorMigration(Event $name, MigrateErrorEvent $event): void
    {
        $this->output->out(
            sprintf(
                '[<bold>%s</bold>] %s: %s <red>error</red>',
                $event->context->dbName,
                $event->action,
                $event->context->filename,
            )
        );

        $this->output->red($event->exception->getMessage());
        $this->output->out($event->context->queryString);
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function error(Event $name, EventInterface $event): void
    {
        $this->output->out(
            sprintf(
                '[<bold>%s</bold>] error: <red>%s</red>',
                $event->getName(),
                $event->getMessage()
            )
        );
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function notice(Event $name, EventInterface $event): void
    {
        $this->output->out(
            sprintf(
                '[<bold>%s</bold>] notice: %s',
                $event->getName(),
                $event->getMessage()
            )
        );
    }
}
