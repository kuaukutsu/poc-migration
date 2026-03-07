<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\exception;

use Throwable;
use kuaukutsu\poc\migration\internal\connection\PDO\Type;

final class ConnectionException extends MigratorException
{
    public function __construct(Type $driver, Throwable $previous)
    {
        parent::__construct(
            message: sprintf('%s:%s', $driver->name, $previous->getMessage()),
            previous: $previous,
        );
    }
}
