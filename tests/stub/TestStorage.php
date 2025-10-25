<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\stub;

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
     */
    public function saveMigration(string $key): void
    {
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
