<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\stub;

use Override;
use kuaukutsu\poc\migration\command\CommandInterface;
use kuaukutsu\poc\migration\command\Options;
use kuaukutsu\poc\migration\Context;

final readonly class TestCommand implements CommandInterface
{
    public function __construct(
        private TestStorage $storage,
    ) {
    }

    #[Override]
    public function fetchApplied(Options $options = new Options()): array
    {
        if ($options->limit > 0) {
            return array_slice($this->storage->getMigration(), 0, $options->limit);
        }

        return $this->storage->getMigration();
    }

    #[Override]
    public function up(Context $context): bool
    {
        $this->storage->set($context->filename, $context->query);
        $this->storage->saveMigration($context->filename, $context->version);
        return true;
    }

    #[Override]
    public function down(Context $context): bool
    {
        $this->storage->set($context->filename, $context->query);
        $this->storage->dropMigration($context->filename);
        return true;
    }

    #[Override]
    public function exec(Context $context): bool
    {
        $this->storage->set($context->filename, $context->query);
        return true;
    }
}
