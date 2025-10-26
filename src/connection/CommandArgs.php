<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\connection;

use kuaukutsu\poc\migration\internal\MigrateArgs;

/**
 * @psalm-internal kuaukutsu\poc\migration
 */
final readonly class CommandArgs
{
    /**
     * @param non-negative-int $limit
     */
    public function __construct(
        public int $limit = 0,
    ) {
        assert($this->limit >= 0);
    }

    public static function makeFromMigrateArgs(MigrateArgs $args): self
    {
        return new self(
            limit: $args->limit,
        );
    }
}
