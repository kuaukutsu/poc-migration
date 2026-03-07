<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration;

/**
 * @api
 * @infection-ignore-all
 */
final readonly class InputArgs
{
    /**
     * @param non-negative-int $limit
     * @param non-negative-int $version
     * @param ?non-empty-string $dbName
     * @param ?non-empty-string $migrationName
     */
    public function __construct(
        public int $limit = 0,
        public int $version = 0,
        public bool $dryRun = false,
        public ?string $dbName = null,
        public ?string $migrationName = null,
        public bool $exactlyAll = false,
        private bool $hasRepeatable = false,
        private bool $applyLatestVersion = false,
    ) {
        assert($this->limit >= 0);
        assert($this->version >= 0);
    }

    public function withResetLimit(): self
    {
        return new self(
            version: $this->version,
            dryRun: $this->dryRun,
            dbName: $this->dbName,
            migrationName: $this->migrationName,
            exactlyAll: $this->exactlyAll,
            hasRepeatable: $this->hasRepeatable,
            applyLatestVersion: $this->applyLatestVersion,
        );
    }

    /**
     * @param non-negative-int $version
     */
    public function withVersion(int $version): self
    {
        return new self(
            version: $version,
            dryRun: $this->dryRun,
            dbName: $this->dbName,
            migrationName: $this->migrationName,
        );
    }

    public function withExactlyAll(): self
    {
        return new self(
            limit: $this->limit,
            version: $this->version,
            dryRun: $this->dryRun,
            dbName: $this->dbName,
            migrationName: $this->migrationName,
            exactlyAll: true,
            hasRepeatable: $this->hasRepeatable,
        );
    }

    public function hasApplyLatestVersion(): bool
    {
        return $this->applyLatestVersion
            && $this->version === 0
            && ($this->limit === 0 || $this->limit > 1);
    }

    public function hasRepeatable(): bool
    {
        return $this->hasRepeatable && $this->dryRun === false;
    }
}
