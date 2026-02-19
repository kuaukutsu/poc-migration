<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\stub;

use RuntimeException;

final class TestStorage
{
    /**
     * @var array<non-empty-string, true>
     */
    private array $table = [];

    /**
     * @var array<non-empty-string, non-empty-string>
     */
    private array $memory = [];

    /**
     * @return list<non-empty-string>
     */
    public function getMigration(): array
    {
        return array_keys($this->table);
    }

    /**
     * @param non-empty-string $key
     * @throws RuntimeException
     */
    public function saveMigration(string $key): void
    {
        if (array_key_exists($key, $this->table)) {
            throw new RuntimeException("record exists");
        }

        $this->table[$key] = true;
    }

    /**
     * @param non-empty-string $key
     */
    public function dropMigration(string $key): void
    {
         unset($this->table[$key]);
    }

    /**
     * @param non-empty-string $key
     */
    public function get(string $key): ?string
    {
        return $this->memory[$key] ?? null;
    }

    /**
     * @param non-empty-string $key
     * @param non-empty-string $value
     */
    public function set(string $key, string $value): void
    {
        $this->memory[$key] = $value;
    }
}
