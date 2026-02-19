<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\filesystem;

use Iterator;
use GlobIterator;
use SplFileInfo;
use kuaukutsu\poc\migration\exception\ConfigurationException;

/**
 * @psalm-internal kuaukutsu\poc\migration
 */
final readonly class Setup
{
    /**
     * @var non-empty-string
     */
    private string $path;

    /**
     * @param non-empty-string $path
     * @param non-empty-string $table
     * @infection-ignore-all
     */
    public function __construct(
        string $path,
        private string $table,
    ) {
        $this->path = rtrim($path, '/') . '/';
    }

    /**
     * @return Iterator<non-empty-string, non-empty-string>
     * @throws ConfigurationException if path not exist
     * @psalm-suppress MoreSpecificReturnType
     */
    public function all(): Iterator
    {
        if (file_exists($this->path) === false) {
            throw new ConfigurationException(
                sprintf('the directory [%s] does not exist.', $this->path)
            );
        }

        /**
         * @var Iterator<SplFileInfo> $iterator
         */
        $iterator = new GlobIterator($this->path . '*.sql');
        foreach ($iterator as $fileInfo) {
            $queryString = $this->prepareCommand($fileInfo->getPathname());
            if ($queryString !== null) {
                /** @phpstan-ignore generator.keyType */
                yield $fileInfo->getFilename() => $queryString;
            }
        }
    }

    /**
     * @return non-empty-string|null
     * @throws ConfigurationException
     */
    private function prepareCommand(string $filepath): ?string
    {
        if (file_exists($filepath) === false) {
            throw new ConfigurationException(
                sprintf('the file [%s] does not exist.', $filepath)
            );
        }

        $queryString = file_get_contents($filepath);
        if ($queryString === '' || $queryString === false) {
            return null;
        }

        /**
         * @var non-empty-string
         */
        return str_replace('%SYSTEM_TABLE%', $this->table, $queryString);
    }
}
