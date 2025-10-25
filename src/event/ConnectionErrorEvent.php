<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\event;

use Override;
use Throwable;

final readonly class ConnectionErrorEvent implements EventInterface
{
    /**
     * @param non-empty-string $dbName
     */
    public function __construct(
        public string $dbName,
        public Throwable $exception,
    ) {
    }

    #[Override]
    public function getEvent(): Event
    {
        return Event::ConnectionError;
    }

    #[Override]
    public function getName(): string
    {
        return $this->dbName;
    }

    #[Override]
    public function getMessage(): string
    {
        return $this->exception->getMessage();
    }
}
