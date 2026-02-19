<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\connection;

interface TransactionInterface extends StatementInterface
{
    public function isActive(): bool;

    public function commit(): bool;

    public function rollback(): bool;
}
