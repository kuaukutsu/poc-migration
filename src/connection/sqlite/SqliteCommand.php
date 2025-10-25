<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\connection\sqlite;

use Override;
use PDO;
use Throwable;
use kuaukutsu\poc\migration\connection\Command;
use kuaukutsu\poc\migration\connection\Params;

/**
 * @psalm-internal kuaukutsu\poc\migration\connection
 */
final readonly class SqliteCommand implements Command
{
    public function __construct(
        private PDO $connection,
        private Params $params,
    ) {
    }

    #[Override]
    public function fetchSavedMigrationNames(): array
    {
        // SQLSTATE[HY000]: General error: 1 no such table:

        $statement = $this->connection->prepare(
            sprintf('SELECT name FROM %s ORDER BY atime DESC', $this->params->table)
        );
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
        $hasTransaction = $this->connection->beginTransaction();

        try {
            $this->connection->exec($queryString);
            $this->connection->exec(
                sprintf(
                    'INSERT INTO %s ("name", "atime") VALUES (\'%s\', \'%s\')',
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
        $hasTransaction = $this->connection->beginTransaction();

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
        $hasTransaction = $this->connection->beginTransaction();

        try {
            $this->connection->exec($queryString);
        } catch (Throwable $exception) {
            if ($hasTransaction) {
                $this->connection->rollBack();
            }

            throw $exception;
        }

        return $hasTransaction === false || $this->connection->commit();
    }
}
