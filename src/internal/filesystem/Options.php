<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\filesystem;

use kuaukutsu\poc\migration\InputOptions;

/**
 * @psalm-internal kuaukutsu\poc\migration
 */
final readonly class Options
{
    /**
     * @param non-negative-int $limit
     */
    public function __construct(
        public int $limit = 0,
    ) {
        assert($this->limit >= 0);
    }

    public static function makeFromInput(InputOptions $args): self
    {
        return new self(
            limit: $args->limit,
        );
    }
}
