<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration;

use Override;
use kuaukutsu\ds\collection\Collection;

/**
 * @extends Collection<Migration>
 */
final class MigrationCollection extends Collection
{
    #[Override]
    public function getType(): string
    {
        return Migration::class;
    }

    /**
     * @param Migration $item
     */
    #[Override]
    protected function indexBy($item): string
    {
        return $item->getName();
    }
}
