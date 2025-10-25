<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\connection;

use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\exception\ConnectionException;
use kuaukutsu\poc\migration\ConnectionDriver;

interface Driver
{
    public function getType(): ConnectionDriver;

    /**
     * @throws ConfigurationException If the driver is not implemented
     * @throws ConnectionException
     */
    public function makeCommand(Params $params): Command;
}
