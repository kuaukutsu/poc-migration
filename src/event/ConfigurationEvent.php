<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\event;

use Override;
use Throwable;

final readonly class ConfigurationEvent implements EventInterface
{
    public function __construct(
        public string $dbName,
        public Throwable $exception,
    ) {
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
