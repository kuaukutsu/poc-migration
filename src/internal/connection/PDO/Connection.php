<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\connection\PDO;

use Override;
use PDO;
use kuaukutsu\poc\migration\connection\ConnectionInterface;
use kuaukutsu\poc\migration\connection\TransactionInterface;
use kuaukutsu\poc\migration\driver\DriverType;

/**
 * @psalm-internal kuaukutsu\poc\migration
 */
final readonly class Connection implements ConnectionInterface
{
    public function __construct(
        private PDO $connection,
        private DriverType $driverType,
    ) {
    }

    #[Override]
    public function beginTransaction(): TransactionInterface
    {
        return Transaction::begin($this->connection, $this->driverType);
    }

    #[Override]
    public function query(string $query): array
    {
        $statement = $this->connection->prepare($query);
        if ($statement->execute()) {
            /**
             * @var list<non-empty-string>
             */
            return $statement->fetchAll(PDO::FETCH_COLUMN);
        }

        return [];
    }

    #[Override]
    public function exec(string $query): void
    {
        $this->connection->exec($query);
    }
}
