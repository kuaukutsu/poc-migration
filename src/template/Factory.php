<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\template;

use Override;

/**
 * @psalm-internal kuaukutsu\poc\migration
 */
final readonly class Factory implements FactoryInterface
{
    #[Override]
    public function makeName(string $name): string
    {
        return sprintf('%d_%s.sql', gmdate('YmdHi'), $name);
    }

    #[Override]
    public function makeBody(): string
    {
        return <<<CODE
-- @up
-- SQL CODE

-- @down
-- SQL CODE
CODE;
    }
}
