<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\connection;

interface StatementInterface
{
    /**
     * @param non-empty-string $query
     * @return array<non-empty-string, non-negative-int> <key: filename; value: version>
     */
    public function fetchRecord(string $query): array;

    /**
     * @param non-empty-string $query
     */
    public function exec(string $query): void;
}
