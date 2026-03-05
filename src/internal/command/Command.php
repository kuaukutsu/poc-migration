<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\command;

use Override;
use Throwable;
use kuaukutsu\poc\migration\connection\ConnectionInterface;
use kuaukutsu\poc\migration\Context;

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
    public function fetchApplied(Args $args = new Args()): array
    {
        $params = [];

        $query = sprintf('SELECT name, version FROM %s', $this->params->table);
        if ($args->version > 0) {
            $query .= ' WHERE version=:version';
            $params['version'] = $args->version;
        }

        $query .= ' ORDER BY atime DESC, name DESC';
        if ($args->limit > 0) {
            $query .= ' LIMIT ' . $args->limit;
        }

        return $this->connection->fetchRecord($query, $params);
    }

    #[Override]
    public function up(Context $context): bool
    {
        if ($context->dryRun) {
            return false;
        }

        $transaction = $this->connection->beginTransaction();

        try {
            $transaction->exec($context->query);
            $transaction->exec(
                sprintf(
                    'INSERT INTO %s (name, version, atime) VALUES (\'%s\', %d, \'%s\')',
                    $this->params->table,
                    $context->filename,
                    $context->version,
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
    public function down(Context $context): bool
    {
        if ($context->dryRun) {
            return false;
        }

        $transaction = $this->connection->beginTransaction();

        try {
            $transaction->exec($context->query);
            $transaction->exec(
                sprintf(
                    'DELETE FROM %s WHERE name=\'%s\'',
                    $this->params->table,
                    $context->filename,
                )
            );
        } catch (Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }

        return $transaction->commit();
    }

    #[Override]
    public function exec(Context $context): bool
    {
        if ($context->dryRun) {
            return false;
        }

        $transaction = $this->connection->beginTransaction();

        try {
            $transaction->exec($context->query);
        } catch (Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }

        return $transaction->commit();
    }
}
