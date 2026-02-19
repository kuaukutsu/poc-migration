<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\stub;

use Override;
use kuaukutsu\poc\migration\driver\DriverInterface;
use kuaukutsu\poc\migration\driver\DriverType;
use kuaukutsu\poc\migration\internal\command\CommandInterface;
use kuaukutsu\poc\migration\internal\command\Params;

final readonly class TestDriver implements DriverInterface
{
    public function __construct(private TestStorage $storage)
    {
    }

    #[Override]
    public function getName(): string
    {
        return DriverType::PDO_SQLITE->value();
    }

    #[Override]
    public function getSetupPath(): string
    {
        return dirname(__DIR__) . '/migration/sqlite/setup';
    }

    #[Override]
    public function makeCommand(Params $params): CommandInterface
    {
        return new TestCommand($this->storage);
    }
}
