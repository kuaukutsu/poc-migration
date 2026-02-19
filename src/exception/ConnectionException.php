<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\exception;

use Throwable;
use kuaukutsu\poc\migration\driver\DriverType;

final class ConnectionException extends MigratorException
{
    public function __construct(DriverType $driver, Throwable $previous)
    {
        parent::__construct(
            message: sprintf('%s:%s', $driver->name, $previous->getMessage()),
            previous: $previous,
        );
    }
}
