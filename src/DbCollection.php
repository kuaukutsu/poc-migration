<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration;

use Override;
use kuaukutsu\ds\collection\Collection;

/**
 * @extends Collection<Db>
 */
final class DbCollection extends Collection
{
    #[Override]
    public function getType(): string
    {
        return Db::class;
    }

    /**
     * @param Db $item
     */
    #[Override]
    protected function indexBy($item): string
    {
        return $item->getName();
    }
}
