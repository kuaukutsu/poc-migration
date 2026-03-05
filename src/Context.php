<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration;

/**
 * @infection-ignore-all IncrementInteger
 */
final readonly class Context
{
    /**
     * @param non-empty-string $dbName
     * @param non-empty-string $filename
     * @param non-empty-string $query
     * @param non-negative-int $version
     */
    public function __construct(
        public string $dbName,
        public string $filename,
        public string $query,
        public int $version = 0,
        public bool $dryRun = false,
    ) {
        assert($this->version >= 0);
    }

    public function getName(): string
    {
        return $this->dbName . '/' . $this->filename;
    }
}
