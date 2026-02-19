<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\event;

use Override;
use Throwable;
use kuaukutsu\poc\migration\Context;

final readonly class MigrateErrorEvent implements EventInterface
{
    /**
     * @param non-empty-string $action
     */
    public function __construct(
        public string $action,
        public Context $context,
        public Throwable $exception,
    ) {
    }

    #[Override]
    public function getName(): string
    {
        return $this->context->getName();
    }

    #[Override]
    public function getMessage(): string
    {
        return sprintf(
            "[%s] %s: %s\r\n%s",
            $this->context->dbName,
            $this->action,
            $this->context->filename,
            $this->exception->getMessage(),
        );
    }
}
