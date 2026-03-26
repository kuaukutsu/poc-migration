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
final class TransactionMysql extends Statement implements TransactionInterface, FactoryTransaction
{
    #[Override]
    public static function begin(PDO $connection): TransactionInterface
    {
        return new self($connection, false);
    }

    #[Override]
    public function isActive(): bool
    {
        $this->transactionActive = $this->transactionActive || $this->connection->inTransaction();

        return $this->transactionActive;
    }

    #[Override]
    public function exec(string $query, array $params = []): void
    {
        if ($this->isActive() === false) {
            $this->transactionActive = $this->tryBeginTransaction($query);
        }

        parent::exec($query, $params);
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

    private function tryBeginTransaction(string $queryString): bool
    {
        $pattern = '/(?:create|alter|drop)\s+(?:table|(?:unique\s?)?index)\s+/i';
        /** @noinspection PhpUnusedLocalVariableInspection */
        if (preg_match($pattern, $queryString, $_)) {
            return false;
        }

        return $this->connection->beginTransaction();
    }
}
