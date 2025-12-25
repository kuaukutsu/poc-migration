<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\exception;

use Throwable;
use kuaukutsu\poc\migration\ConnectionDriver;

final class ConnectionException extends MigratorException
{
    public function __construct(ConnectionDriver $driver, Throwable $previous)
    {
        parent::__construct(
            message: sprintf('%s:%s', $driver->name, $previous->getMessage()),
            previous: $previous,
        );
    }
}
