<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\connection;

interface StatementInterface
{
    /**
     * @param non-empty-string $query
     * @param array<string, scalar|null> $params
     * @return array<non-empty-string, non-negative-int> <key: filename; value: version>
     * @throws \Throwable
     */
    public function fetchRecord(string $query, array $params = []): array;

    /**
     * @param non-empty-string $query
     * @param array<string, scalar|null> $params
     * @throws \Throwable
     */
    public function exec(string $query, array $params = []): void;
}
