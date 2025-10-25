<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\event;

enum EventAction
{
    case up;
    case down;
    case repeatable;
    case fixture;
    case initialization;
}
