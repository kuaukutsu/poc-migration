<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal;

final readonly class MigrateArgs
{
    /**
     * @param non-negative-int $limit
     */
    public function __construct(
        public int $limit = 0,
        public bool $dryRun = false,
    ) {
        assert($this->limit >= 0);
    }

    /**
     * @param positive-int $limit
     */
    public function withLimit(int $limit): self
    {
        return new self(
            limit: $limit,
            dryRun: $this->dryRun,
        );
    }
}
