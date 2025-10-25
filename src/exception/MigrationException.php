<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\exception;

use Throwable;
use RuntimeException;

final class MigrationException extends RuntimeException
{
    public function __construct(string $filename, Throwable $previous)
    {
        parent::__construct(
            message: sprintf('%s: %s', $filename, $previous->getMessage()),
            previous: $previous,
        );
    }
}
