<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\command;

use kuaukutsu\poc\migration\InputArgs;

/**
 * @psalm-internal kuaukutsu\poc\migration
 */
final readonly class Args
{
    /**
     * @param non-negative-int $limit
     */
    public function __construct(
        public int $limit = 0,
    ) {
        assert($this->limit >= 0);
    }

    public static function makeFromInput(InputArgs $args): self
    {
        return new self(
            limit: $args->limit,
        );
    }
}
