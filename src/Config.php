<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration;

/**
 * @api
 */
final readonly class Config
{
    /**
     * @param non-empty-string $table
     */
    public function __construct(
        public string $table = 'migration',
        public template\FactoryInterface $templFactory = new template\Factory(),
    ) {
    }
}
