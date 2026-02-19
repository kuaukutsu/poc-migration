<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration;

final readonly class Context
{
    /**
     * @param non-empty-string $dbName
     * @param non-empty-string $filename
     * @param non-empty-string $queryString
     */
    public function __construct(
        public string $dbName,
        public string $filename,
        public string $queryString,
        public bool $dryRun = false,
    ) {
    }

    public function getName(): string
    {
        return $this->dbName . '/' . $this->filename;
    }
}
