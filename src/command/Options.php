<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\command;

use kuaukutsu\poc\migration\InputOptions;

final readonly class Options
{
    /**
     * @param non-negative-int $limit
     * @param non-negative-int $version
     */
    public function __construct(
        public int $limit = 0,
        public int $version = 0,
    ) {
        assert($this->limit >= 0);
        assert($this->version >= 0);
    }

    public static function makeFromInput(InputOptions $args): self
    {
        return new self(
            limit: $args->limit,
            version: $args->version,
        );
    }

    /**
     * @param non-negative-int $version
     */
    public function withVersion(int $version): self
    {
        return new self(
            limit: $this->limit,
            version: $version,
        );
    }
}
