<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\exception;

use RuntimeException;

final class ConfigurationException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
