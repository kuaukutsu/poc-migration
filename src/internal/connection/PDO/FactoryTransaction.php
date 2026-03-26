<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\connection\PDO;

use PDO;
use kuaukutsu\poc\migration\connection\TransactionInterface;

/**
 * @psalm-internal kuaukutsu\poc\migration\internal\connection\PDO
 */
interface FactoryTransaction
{
    public static function begin(PDO $connection): TransactionInterface;
}
