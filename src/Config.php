<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration;

use kuaukutsu\poc\migration\exception\ConfigurationException;

/**
 * @api
 */
final readonly class Config
{
    /**
     * @param non-empty-string $table
     * @throws ConfigurationException
     */
    public function __construct(
        public string $table = 'migration',
        public template\FactoryInterface $templFactory = new template\Factory(),
    ) {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new ConfigurationException(
                "Table name '$table' contains invalid characters."
            );
        }
    }
}
