<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\connection\mysql;

use Override;
use PDO;
use Throwable;
use kuaukutsu\poc\migration\connection\Command;
use kuaukutsu\poc\migration\connection\CommandArgs;
use kuaukutsu\poc\migration\connection\Params;

final readonly class MysqlCommand implements Command
{
    public function __construct(
        private PDO $connection,
        private Params $params,
    ) {
    }

    #[Override]
    public function fetchSavedMigrationNames(CommandArgs $args = new CommandArgs()): array
    {
        $query = sprintf('SELECT name FROM %s ORDER BY atime DESC, name DESC', $this->params->table);
        if ($args->limit > 0) {
            $query = sprintf(
                'SELECT name FROM %s ORDER BY atime DESC, name DESC LIMIT %d',
                $this->params->table,
                $args->limit
            );
        }

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
    public function up(string $queryString, string $filename): bool
    {
        $hasTransaction = $this->tryBeginTransaction($queryString);

        try {
            $this->connection->exec($queryString);
            $this->connection->exec(
                sprintf(
                    'INSERT INTO %s (`name`, `atime`) VALUES (\'%s\', \'%s\')',
                    $this->params->table,
                    $filename,
                    gmdate('Y-m-d H:i:s'),
                )
            );
        } catch (Throwable $exception) {
            if ($hasTransaction) {
                $this->connection->rollBack();
            }

            throw $exception;
        }

        return $hasTransaction === false || $this->connection->commit();
    }

    #[Override]
    public function down(string $queryString, string $filename): bool
    {
        $hasTransaction = $this->tryBeginTransaction($queryString);

        try {
            $this->connection->exec($queryString);
            $this->connection->exec(
                sprintf(
                    'DELETE FROM %s WHERE name=\'%s\'',
                    $this->params->table,
                    $filename,
                )
            );
        } catch (Throwable $exception) {
            if ($hasTransaction) {
                $this->connection->rollBack();
            }

            throw $exception;
        }

        return $hasTransaction === false || $this->connection->commit();
    }

    #[Override]
    public function exec(string $queryString, string $filename): bool
    {
        $hasTransaction = $this->tryBeginTransaction($queryString);

        try {
            $this->connection->exec($queryString);
        } catch (Throwable $exception) {
            if ($hasTransaction) {
                $this->connection->rollBack();
            }

            throw $exception;
        }

        // There is no active transaction

        return $hasTransaction === false || $this->connection->commit();
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
