<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\connection\PDO;

use Override;
use PDO;
use kuaukutsu\poc\migration\connection\TransactionInterface;

/**
 * @psalm-internal kuaukutsu\poc\migration
 * @infection-ignore-all вынести в отдельный пакет
 */
final class Transaction extends Statement implements TransactionInterface, FactoryTransaction
{
    #[Override]
    public static function begin(PDO $connection): TransactionInterface
    {
        return new self($connection, $connection->beginTransaction());
    }

    #[Override]
    public function isActive(): bool
    {
        $this->transactionActive = $this->transactionActive || $this->connection->inTransaction();

        return $this->transactionActive;
    }

    #[Override]
    public function commit(): bool
    {
        if ($this->isActive()) {
            $this->transactionActive = false;
            return $this->connection->commit();
        }

        return true;
    }

    #[Override]
    public function rollback(): bool
    {
        if ($this->isActive()) {
            $this->transactionActive = false;
            return $this->connection->rollBack();
        }

        return true;
    }
}
