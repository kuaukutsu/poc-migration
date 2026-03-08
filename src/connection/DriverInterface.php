<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\connection;

use kuaukutsu\poc\migration\command\CommandInterface;
use kuaukutsu\poc\migration\exception\ConnectionException;
use kuaukutsu\poc\migration\internal\command\Params;

interface DriverInterface
{
    /**
     * @return non-empty-lowercase-string
     */
    public function getName(): string;

    /**
     * @return non-empty-lowercase-string
     */
    public function getSourceName(): string;

    /**
     * @return non-empty-string
     */
    public function getSetupPath(): string;

    /**
     * @throws ConnectionException
     */
    public function makeCommand(Params $params): CommandInterface;
}
