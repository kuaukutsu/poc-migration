<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\exception;

use Throwable;
use RuntimeException;
use kuaukutsu\poc\migration\ConnectionDriver;

final class ConnectionException extends RuntimeException
{
    public function __construct(ConnectionDriver $driver, Throwable $previous)
    {
        parent::__construct(
            message: sprintf('%s:%s', $driver->name, $previous->getMessage()),
            previous: $previous,
        );
    }
}
