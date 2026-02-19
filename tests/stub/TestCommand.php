<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\stub;

use Override;
use kuaukutsu\poc\migration\internal\command;
use kuaukutsu\poc\migration\internal\command\CommandInterface;

final readonly class TestCommand implements CommandInterface
{
    public function __construct(
        private TestStorage $storage,
    ) {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function fetchSavedMigrationNames(command\Args $args = new command\Args()): array
    {
        if ($args->limit > 0) {
            return array_slice($this->storage->getMigration(), 0, $args->limit);
        }

        return $this->storage->getMigration();
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function up(string $queryString, string $filename): bool
    {
        $this->storage->set($filename, $queryString);
        $this->storage->saveMigration($filename);
        return true;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function down(string $queryString, string $filename): bool
    {
        $this->storage->set($filename, $queryString);
        $this->storage->dropMigration($filename);
        return true;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function exec(string $queryString, string $filename): bool
    {
        $this->storage->set($filename, $queryString);
        return true;
    }
}
