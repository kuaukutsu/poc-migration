<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\command;

use Throwable;
use kuaukutsu\poc\migration\internal\command;

interface CommandInterface
{
    /**
     * @return list<non-empty-string>
     */
    public function fetchSavedMigrationNames(command\Args $args = new command\Args()): array;

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
