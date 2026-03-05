<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration;

final readonly class InputArgs
{
    /**
     * @param non-negative-int $limit
     */
    public function __construct(
        public int $limit = 0,
        public bool $dryRun = false,
        public ?string $dbName = null,
        private bool $hasRepeatable = false,
    ) {
        assert($this->limit >= 0);
    }

    public function withResetLimit(): self
    {
        return new self(
            dryRun: $this->dryRun,
            dbName: $this->dbName,
            hasRepeatable: $this->hasRepeatable,
        );
    }

    public function hasRepeatable(): bool
    {
        return $this->hasRepeatable && $this->dryRun === false;
    }
}
