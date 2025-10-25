<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\connection\mysql;

use PDO;
use Override;
use kuaukutsu\poc\migration\connection\Command;
use kuaukutsu\poc\migration\connection\Params;

final readonly class MyISAMCommand implements Command
{
    public function __construct(
        private PDO $connection,
        private Params $params,
    ) {
    }

    #[Override]
    public function fetchSavedMigrationNames(): array
    {
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
    public function up(string $queryString, string $filename): true
    {
        $this->connection->exec($queryString);
        $this->connection->exec(
            sprintf(
                'INSERT INTO %s ("name", "atime") VALUES (\'%s\', \'%s\')',
                $this->params->table,
                $filename,
                gmdate('Y-m-d H:i:s'),
            )
        );

        return true;
    }

    #[Override]
    public function down(string $queryString, string $filename): true
    {
        $this->connection->exec($queryString);
        $this->connection->exec(
            sprintf(
                'DELETE FROM %s WHERE name=\'%s\'',
                $this->params->table,
                $filename,
            )
        );

        return true;
    }

    #[Override]
    public function exec(string $queryString, string $filename): true
    {
        $this->connection->exec($queryString);
        return true;
    }
}
