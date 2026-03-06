<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\template;

interface FactoryInterface
{
    /**
     * @param non-empty-string $name
     * @return non-empty-string
     */
    public function makeName(string $name): string;

    public function makeBody(): string;
}
