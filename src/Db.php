<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration;

use kuaukutsu\poc\migration\connection\Driver;

final readonly class Db
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
        public Driver $driver,
        public string $table = 'migration',
    ) {
        $this->name = $this->driver->getType()->value . '/' . basename($this->path);
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
    public function getSetupFilepath(): string
    {
        return __DIR__ . sprintf('/connection/%s/migration/', $this->driver->getType()->value);
    }
}
