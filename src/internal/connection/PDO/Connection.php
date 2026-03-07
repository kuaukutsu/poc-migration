<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\connection\PDO;

use Override;
use PDO;
use kuaukutsu\poc\migration\connection\ConnectionInterface;
use kuaukutsu\poc\migration\connection\TransactionInterface;

/**
 * @psalm-internal kuaukutsu\poc\migration
 */
final readonly class Connection implements ConnectionInterface
{
    public function __construct(
        private PDO $connection,
        private Type $driverType,
    ) {
    }

    #[Override]
    public function beginTransaction(): TransactionInterface
    {
        return $this->driverType === Type::PDO_MYSQL
            ? TransactionMysql::begin($this->connection)
            : Transaction::begin($this->connection);
    }

    #[Override]
    public function fetchRecord(string $query, array $params = []): array
    {
        $statement = $this->connection->prepare($query);
        if ($statement->execute($params)) {
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
        $this->connection->exec($query);
    }
}
