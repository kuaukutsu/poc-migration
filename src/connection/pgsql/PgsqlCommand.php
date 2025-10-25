<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\connection\pgsql;

use Override;
use PDO;
use Throwable;
use kuaukutsu\poc\migration\connection\Command;
use kuaukutsu\poc\migration\connection\Params;

/**
 * @psalm-internal kuaukutsu\poc\migration\connection
 */
final readonly class PgsqlCommand implements Command
{
    public function __construct(
        private PDO $connection,
        private Params $params,
    ) {
    }

    #[Override]
    public function fetchSavedMigrationNames(): array
    {
        // SQLSTATE[42P01]: Undefined table: 7 ERROR:  relation "migration" does not exist

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
    public function up(string $sql, string $filename): bool
    {
        $this->connection->beginTransaction();

        try {
            $this->connection->exec($sql);
            $this->connection->exec(
                sprintf(
                    'INSERT INTO %s ("name", "atime") VALUES (\'%s\', \'%s\')',
                    $this->params->table,
                    $filename,
                    gmdate('Y-m-d H:i:s'),
                )
            );
        } catch (Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }

        return $this->connection->commit();
    }

    #[Override]
    public function down(string $sql, string $filename): bool
    {
        $this->connection->beginTransaction();

        try {
            $this->connection->exec($sql);
            $this->connection->exec(
                sprintf(
                    'DELETE FROM %s WHERE name=\'%s\'',
                    $this->params->table,
                    $filename,
                )
            );
        } catch (Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }

        return $this->connection->commit();
    }

    #[Override]
    public function exec(string $sql, string $filename): bool
    {
        $this->connection->beginTransaction();

        try {
            $this->connection->exec($sql);
        } catch (Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }

        return $this->connection->commit();
    }
}
