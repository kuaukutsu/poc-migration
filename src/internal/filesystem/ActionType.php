<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\filesystem;

/**
 * @psalm-internal kuaukutsu\poc\migration\internal\filesystem
 */
enum ActionType: string
{
    case UP = 'up';
    case DOWN = 'down';
    case SKIP = 'skip';
}
