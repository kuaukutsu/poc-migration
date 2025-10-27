<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\event;

use Override;
use Throwable;
use kuaukutsu\poc\migration\Db;

final readonly class ConfigurationEvent implements EventInterface
{
    public function __construct(
        public Db $db,
        public Throwable $exception,
    ) {
    }

    #[Override]
    public function getName(): string
    {
        return $this->db->getName();
    }

    #[Override]
    public function getMessage(): string
    {
        return $this->exception->getMessage();
    }
}
