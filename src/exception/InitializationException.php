<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\exception;

use Throwable;
use RuntimeException;

final class InitializationException extends RuntimeException
{
    public function __construct(string $message, Throwable $previous)
    {
        parent::__construct(message: $message, previous: $previous);
    }
}
