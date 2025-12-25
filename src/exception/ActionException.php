<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\exception;

use Throwable;

final class ActionException extends MigratorException
{
    public function __construct(string $filename, Throwable $previous)
    {
        parent::__construct(
            message: sprintf('%s: %s', $filename, $previous->getMessage()),
            previous: $previous,
        );
    }
}
