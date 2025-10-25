<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\event;

interface EventInterface
{
    public function getEvent(): Event;

    public function getName(): string;

    public function getMessage(): string;
}
