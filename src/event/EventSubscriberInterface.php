<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\event;

interface EventSubscriberInterface
{
    /**
     * @return array<string, callable(Event $name, EventInterface $event):void>
     */
    public function subscriptions(): array;
}
