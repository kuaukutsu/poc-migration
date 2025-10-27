<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\event;

use Throwable;

/**
 * @psalm-internal kuaukutsu\poc\migration
 */
final readonly class EventDispatcher
{
    /**
     * @var array<string, list<callable(Event $name, EventInterface $event):void>>
     */
    private array $eventHandlers;

    /**
     * @param list<EventSubscriberInterface> $eventSubscribers
     */
    public function __construct(array $eventSubscribers)
    {
        $subscriptions = [];
        foreach ($eventSubscribers as $subscriber) {
            foreach ($subscriber->subscriptions() as $name => $callback) {
                $subscriptions[$name][] = $callback;
            }
        }

        $this->eventHandlers = $subscriptions;
    }

    public function trigger(Event $name, EventInterface $event): void
    {
        if (array_key_exists($name->value, $this->eventHandlers)) {
            foreach ($this->eventHandlers[$name->value] as $subscriberCallback) {
                try {
                    $subscriberCallback($name, $event);
                } catch (Throwable) {
                }
            }
        }
    }
}
