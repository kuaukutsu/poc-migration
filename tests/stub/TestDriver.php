<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\stub;

use kuaukutsu\poc\migration\connection\Command;
use kuaukutsu\poc\migration\connection\Driver;
use kuaukutsu\poc\migration\connection\Params;
use kuaukutsu\poc\migration\ConnectionDriver;
use Override;

final readonly class TestDriver implements Driver
{
    #[Override]
    public function getType(): ConnectionDriver
    {
        return ConnectionDriver::UNSUPPORTED;
    }

    #[Override]
    public function makeCommand(Params $params): Command
    {
        return new TestCommand();
    }
}
