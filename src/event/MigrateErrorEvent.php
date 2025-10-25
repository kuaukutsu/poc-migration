<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\event;

use Override;
use Throwable;
use kuaukutsu\poc\migration\internal\MigrateContext;

final readonly class MigrateErrorEvent implements EventInterface
{
    /**
     * @param non-empty-string $action
     */
    public function __construct(
        public string $action,
        public MigrateContext $migrate,
        public Throwable $exception,
    ) {
    }

    #[Override]
    public function getEvent(): Event
    {
        return Event::MigrateError;
    }

    #[Override]
    public function getName(): string
    {
        return $this->migrate->getName();
    }

    #[Override]
    public function getMessage(): string
    {
        return $this->exception->getMessage();
    }
}
