<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\connection;

use Throwable;

interface Command
{
    /**
     * @return list<non-empty-string>
     */
    public function fetchSavedMigrationNames(CommandArgs $args = new CommandArgs()): array;

    /**
     * @param non-empty-string $queryString
     * @param non-empty-string $filename
     * @throws Throwable
     */
    public function up(string $queryString, string $filename): bool;

    /**
     * @param non-empty-string $queryString
     * @param non-empty-string $filename
     * @throws Throwable
     */
    public function down(string $queryString, string $filename): bool;

    /**
     * @param non-empty-string $queryString
     * @param non-empty-string $filename
     * @throws Throwable
     */
    public function exec(string $queryString, string $filename): bool;
}
