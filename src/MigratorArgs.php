<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration;

final readonly class MigratorArgs
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
}
