<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\connection;

interface StatementInterface
{
    /**
     * @return list<non-empty-string>
     */
    public function query(string $query): array;

    public function exec(string $query): void;
}
