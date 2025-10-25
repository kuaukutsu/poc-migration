<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\stub;

use kuaukutsu\poc\migration\connection\Command;
use Override;

final readonly class TestCommand implements Command
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function fetchSavedMigrationNames(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function up(string $sql, string $filename): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function down(string $sql, string $filename): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function exec(string $sql, string $filename): bool
    {
        return true;
    }
}
