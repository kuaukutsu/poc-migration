<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\event;

interface EventPublisherInterface
{
    public function on(EventSubscriberInterface $subscriber): void;

    public function off(EventSubscriberInterface $subscriber): void;
}
