<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\command;

use Override;
use Throwable;
use kuaukutsu\poc\migration\connection\ConnectionInterface;

/**
 * @psalm-internal kuaukutsu\poc\migration
 */
final readonly class Command implements CommandInterface
{
    public function __construct(
        private ConnectionInterface $connection,
        private Params $params,
    ) {
    }

    #[Override]
    public function fetchSavedMigrationNames(Args $args = new Args()): array
    {
        // SQLSTATE[42P01]: Undefined table: 7 ERROR:  relation "migration" does not exist
        $query = sprintf('SELECT name FROM %s ORDER BY atime DESC, name DESC', $this->params->table);
        if ($args->limit > 0) {
            $query .= ' LIMIT ' . $args->limit;
        }

        return $this->connection->query($query);
    }

    #[Override]
    public function up(string $queryString, string $filename): bool
    {
        $transaction = $this->connection->beginTransaction();

        try {
            $transaction->exec($queryString);
            $transaction->exec(
                sprintf(
                    'INSERT INTO %s (name, atime) VALUES (\'%s\', \'%s\')',
                    $this->params->table,
                    $filename,
                    gmdate('Y-m-d H:i:s'),
                )
            );
        } catch (Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }

        return $transaction->commit();
    }

    #[Override]
    public function down(string $queryString, string $filename): bool
    {
        $transaction = $this->connection->beginTransaction();

        try {
            $transaction->exec($queryString);
            $transaction->exec(
                sprintf(
                    'DELETE FROM %s WHERE name=\'%s\'',
                    $this->params->table,
                    $filename,
                )
            );
        } catch (Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }

        return $transaction->commit();
    }

    #[Override]
    public function exec(string $queryString, string $filename): bool
    {
        $transaction = $this->connection->beginTransaction();

        try {
            $transaction->exec($queryString);
        } catch (Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }

        return $transaction->commit();
    }
}
