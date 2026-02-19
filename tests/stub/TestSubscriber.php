<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\tests\stub;

use Override;
use kuaukutsu\poc\migration\event\Event;
use kuaukutsu\poc\migration\event\EventInterface;
use kuaukutsu\poc\migration\event\EventSubscriberInterface;

final class TestSubscriber implements EventSubscriberInterface
{
    /**
     * @var array<string, string>
     */
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

    public function clear(): void
    {
        $this->storage = [];
    }
}
