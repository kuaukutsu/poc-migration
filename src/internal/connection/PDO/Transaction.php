<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\connection\PDO;

use Override;
use PDO;
use kuaukutsu\poc\migration\connection\TransactionInterface;
use kuaukutsu\poc\migration\driver\DriverType;

/**
 * @psalm-internal kuaukutsu\poc\migration
 */
final class Transaction implements TransactionInterface
{
    private function __construct(
        private readonly PDO $connection,
        private bool $transactionActive,
    ) {
    }

    public static function begin(PDO $connection, DriverType $driverType): TransactionInterface
    {
        if ($driverType === DriverType::PDO_MYSQL) {
            return new self($connection, false);
        }

        return new self($connection, $connection->beginTransaction());
    }

    #[Override]
    public function isActive(): bool
    {
        return $this->transactionActive;
    }

    #[Override]
    public function fetchRecord(string $query): array
    {
        $statement = $this->connection->prepare($query);
        if ($statement->execute()) {
            /**
             * @var array<non-empty-string, non-negative-int>
             */
            return $statement->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        return [];
    }

    #[Override]
    public function exec(string $query): void
    {
        if ($this->isActive() === false) {
            $this->transactionActive = $this->tryBeginTransaction($query);
        }

        $this->connection->exec($query);
    }

    #[Override]
    public function commit(): bool
    {
        if ($this->isActive()) {
            return $this->connection->commit();
        }

        return true;
    }

    #[Override]
    public function rollback(): bool
    {
        if ($this->isActive()) {
            return $this->connection->rollBack();
        }

        return true;
    }

    private function tryBeginTransaction(string $queryString): bool
    {
        $pattern = '/(?:create|alter|drop)\s+(?:table|(?:unique\s?)?index)\s+/i';
        if (preg_match($pattern, $queryString, $_)) {
            return false;
        }

        return $this->connection->beginTransaction();
    }
}
