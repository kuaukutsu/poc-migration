<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\connection\PDO;

use PDO;
use kuaukutsu\poc\migration\exception\PrepareException;
use kuaukutsu\poc\migration\connection\StatementInterface;

/**
 * @psalm-internal kuaukutsu\poc\migration\internal\connection\PDO
 */
abstract class Statement implements StatementInterface
{
    protected function __construct(
        protected readonly PDO $connection,
        protected bool $transactionActive,
    ) {
    }

    #[\Override]
    public function fetchRecord(string $query, array $params = []): array
    {
        $statement = $this->connection->prepare($query);
        if ($statement === false) {
            $this->prepareException();
        }

        if ($statement->execute($params)) {
            /**
             * @var array<non-empty-string, non-negative-int>
             */
            return $statement->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        return [];
    }

    #[\Override]
    public function exec(string $query, array $params = []): void
    {
        if ($params === []) {
            $this->connection->exec($query);
            return;
        }

        $statement = $this->connection->prepare($query);
        if ($statement === false) {
            $this->prepareException();
        }

        $statement->execute($params);
    }

    /**
     * @throws PrepareException
     */
    private function prepareException(): never
    {
        /**
         * @var array{0: string, 1: int, 2: string} $info PDO::errorInfo Spec
         */
        $info = $this->connection->errorInfo();
        throw new PrepareException(
            (string)new ErrorInfo($info)
        );
    }
}
