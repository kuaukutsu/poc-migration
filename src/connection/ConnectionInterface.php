<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\connection;

interface ConnectionInterface extends StatementInterface
{
    public function beginTransaction(): TransactionInterface;
}
