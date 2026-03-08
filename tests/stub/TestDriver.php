<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\stub;

use Override;
use kuaukutsu\poc\migration\command\CommandInterface;
use kuaukutsu\poc\migration\connection\DriverInterface;
use kuaukutsu\poc\migration\Config;

final readonly class TestDriver implements DriverInterface
{
    public function __construct(private TestStorage $storage)
    {
    }

    #[Override]
    public function getName(): string
    {
        return 'test';
    }

    public function getSourceName(): string
    {
        return 'storage';
    }

    #[Override]
    public function getSetupPath(): string
    {
        return dirname(__DIR__) . '/migration/sqlite/setup';
    }

    #[Override]
    public function makeCommand(Config $config): CommandInterface
    {
        return new TestCommand($this->storage);
    }
}
