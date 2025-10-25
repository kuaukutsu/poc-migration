<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\stub;

use Override;
use kuaukutsu\poc\migration\connection\Command;
use kuaukutsu\poc\migration\connection\Driver;
use kuaukutsu\poc\migration\connection\Params;
use kuaukutsu\poc\migration\ConnectionDriver;

final readonly class TestDriver implements Driver
{
    public function __construct(private TestStorage $storage)
    {
    }

    #[Override]
    public function getType(): ConnectionDriver
    {
        return ConnectionDriver::PDO_SQLITE;
    }

    #[Override]
    public function makeCommand(Params $params): Command
    {
        return new TestCommand($this->storage);
    }
}
