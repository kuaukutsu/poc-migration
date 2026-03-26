<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\connection\PDO;

/**
 * @psalm-internal kuaukutsu\poc\migration\internal\connection\PDO
 * @see https://www.php.net/manual/en/pdo.errorinfo.php
 */
final readonly class ErrorInfo implements \Stringable
{
    /**
     * @var non-empty-string
     */
    private string $message;

    /**
     * @param array{0: string, 1: int, 2: string} $errorInfo
     */
    public function __construct(array $errorInfo)
    {
        $this->message = sprintf('SQLSTATE[%s]: error: %s', $errorInfo[0], $errorInfo[2]);
    }

    /**
     * @return non-empty-string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getMessage();
    }
}
