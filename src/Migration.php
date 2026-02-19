<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration;

use kuaukutsu\poc\migration\driver\DriverInterface;
use kuaukutsu\poc\migration\exception\ConnectionException;
use kuaukutsu\poc\migration\internal\command\CommandInterface;
use kuaukutsu\poc\migration\internal\command\Params;

final readonly class Migration
{
    /**
     * @var non-empty-string
     */
    private string $name;

    /**
     * @param non-empty-string $path
     * @param non-empty-string $table
     */
    public function __construct(
        public string $path,
        private DriverInterface $driver,
        public string $table = 'migration',
    ) {
        $this->name = $this->driver->getName() . '/' . basename($this->path);
    }

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return non-empty-string
     */
    public function getSetupPath(): string
    {
        return $this->driver->getSetupPath();
    }

    /**
     * @throws ConnectionException
     */
    public function getCommand(): CommandInterface
    {
        return $this->driver->makeCommand(new Params(table: $this->table));
    }
}
