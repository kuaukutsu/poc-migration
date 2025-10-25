<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\connection;

use Throwable;

interface Command
{
    /**
     * @return list<non-empty-string>
     */
    public function fetchSavedMigrationNames(): array;

    /**
     * @param non-empty-string $sql
     * @param non-empty-string $filename
     * @throws Throwable
     */
    public function up(string $sql, string $filename): bool;

    /**
     * @param non-empty-string $sql
     * @param non-empty-string $filename
     * @throws Throwable
     */
    public function down(string $sql, string $filename): bool;

    /**
     * @param non-empty-string $sql
     * @param non-empty-string $filename
     * @throws Throwable
     */
    public function exec(string $sql, string $filename): bool;
}
