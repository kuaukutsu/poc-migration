<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\event;

interface EventInterface
{
    public function getName(): string;

    public function getMessage(): string;
}
