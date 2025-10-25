<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\event;

use Override;
use Throwable;

final readonly class FilesystemErrorEvent implements EventInterface
{
    /**
     * @param non-empty-string $path
     */
    public function __construct(
        public string $path,
        public Throwable $exception,
    ) {
    }

    #[Override]
    public function getEvent(): Event
    {
        return Event::FileError;
    }

    #[Override]
    public function getName(): string
    {
        return $this->path;
    }

    #[Override]
    public function getMessage(): string
    {
        return $this->exception->getMessage();
    }
}
