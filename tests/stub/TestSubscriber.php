<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\stub;

use kuaukutsu\poc\migration\event\Event;
use kuaukutsu\poc\migration\event\EventInterface;
use kuaukutsu\poc\migration\event\EventSubscriberInterface;
use Override;

final class TestSubscriber implements EventSubscriberInterface
{
    private array $storage = [];

    #[Override]
    public function subscriptions(): array
    {
        $subscriptions = [];
        foreach (Event::cases() as $event) {
            $subscriptions[$event->value] = $this->set(...);
        }

        /**
         * @var non-empty-array<string, callable(Event $name, EventInterface $event):void> $subscriptions
         * @phpstan-ignore varTag.nativeType
         */
        return $subscriptions;
    }

    public function set(Event $name, EventInterface $event): void
    {
        $this->storage[$name->value] = $event->getMessage();
    }

    public function get(Event $name): string
    {
        return $this->storage[$name->value] ?? '';
    }
}
