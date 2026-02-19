<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\command;

/**
 * @psalm-internal kuaukutsu\poc\migration
 */
final readonly class Params
{
    /**
     * @param non-empty-string $table
     */
    public function __construct(
        public string $table,
    ) {
    }
}
