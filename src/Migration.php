<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration;

use kuaukutsu\poc\migration\command\CommandInterface;
use kuaukutsu\poc\migration\connection\DriverInterface;
use kuaukutsu\poc\migration\exception\ConnectionException;

/**
 * @api
 */
final readonly class Migration
{
    /**
     * @var non-empty-string
     */
    private string $name;

    /**
     * @param non-empty-string $path
     */
    public function __construct(
        public string $path,
        private DriverInterface $driver,
        public Config $config = new Config(),
    ) {
        $this->name = $this->driver->getName() . '/' . $this->driver->getSourceName();
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
        return $this->driver->makeCommand($this->config);
    }
}
