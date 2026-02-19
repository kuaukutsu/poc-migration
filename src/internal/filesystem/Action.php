<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\filesystem;

use Iterator;
use GlobIterator;
use RegexIterator;
use kuaukutsu\poc\migration\exception\ConfigurationException;

/**
 * @psalm-internal kuaukutsu\poc\migration
 */
final readonly class Action
{
    private string $path;

    /**
     * @param non-empty-string $path
     * @infection-ignore-all
     */
    public function __construct(string $path)
    {
        $this->path = rtrim($path, '/') . '/';
    }

    /**
     * @param list<non-empty-string> $listSavedFilename
     * @return Iterator<non-empty-string, non-empty-string>
     * @throws ConfigurationException
     */
    public function up(array $listSavedFilename, Args $args = new Args()): Iterator
    {
        $_iternum = 0;
        foreach ($this->makeIterator($this->path) as $matchFilename) {
            $filepath = $matchFilename[0];
            if (in_array(basename($filepath), $listSavedFilename, true)) {
                continue;
            }

            if ($args->limit > 0 && $_iternum++ > $args->limit) {
                break;
            }

            $command = $this->prepareCommand($filepath, 'up');
            if ($command !== null) {
                /** @phpstan-ignore generator.keyType */
                yield basename($filepath) => $command;
            }
        }
    }

    /**
     * @param list<non-empty-string> $listSavedFilename
     * @return Iterator<non-empty-string, non-empty-string>
     * @throws ConfigurationException
     */
    public function down(array $listSavedFilename): Iterator
    {
        foreach ($listSavedFilename as $filename) {
            $command = $this->prepareCommand($this->path . $filename, 'down');
            if ($command !== null) {
                yield $filename => $command;
            }
        }
    }

    /**
     * @return Iterator<non-empty-string, non-empty-string>
     * @throws ConfigurationException
     */
    public function fixture(Args $args = new Args()): Iterator
    {
        $_iternum = 0;
        foreach ($this->makeIterator(rtrim($this->path, '/') . '-fixture/') as $matchFilename) {
            if ($args->limit > 0 && $_iternum++ > $args->limit) {
                break;
            }

            $filepath = $matchFilename[0];
            $command = $this->prepareCommand($filepath, 'up');
            if ($command !== null) {
                /** @phpstan-ignore generator.keyType */
                yield basename($filepath) => $command;
            }
        }
    }

    /**
     * @return Iterator<non-empty-string, non-empty-string>
     * @throws ConfigurationException
     */
    public function repeatable(): Iterator
    {
        foreach ($this->makeIterator(rtrim($this->path, '/') . '-repeatable/') as $matchFilename) {
            $filepath = $matchFilename[0];
            $command = $this->prepareCommand($filepath, 'up');
            if ($command !== null) {
                /** @phpstan-ignore generator.keyType */
                yield basename($filepath) => $command;
            }
        }
    }

    /**
     * @return Iterator<list<non-empty-string>>
     * @throws ConfigurationException
     */
    private function makeIterator(string $path): Iterator
    {
        if (file_exists($path)) {
            /**
             * @var Iterator<list<non-empty-string>>
             */
            return new RegexIterator(
                new GlobIterator($path . '*.sql'),
                '/.+(?<!skip)+\.sql/i',
                RegexIterator::GET_MATCH,
            );
        }

        throw new ConfigurationException(
            sprintf('the directory [%s] does not exist.', $path)
        );
    }

    /**
     * @param non-empty-string $filepath
     * @param non-empty-string $actionKey enum('up', 'down', 'skip')
     * @return non-empty-string|null
     * @throws ConfigurationException
     */
    private function prepareCommand(string $filepath, string $actionKey): ?string
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

        /** @psalm-suppress RiskyTruthyFalsyComparison */
        if (preg_match_all('/^--\s?@(?<action>\w+)\s?\R(?<query>(?:(?!^--\s?@).)*)/ms', $queryString, $match)) {
            /**
             * @var array{"action": non-empty-string[], "query": non-empty-string[]} $match
             * @phpstan-ignore varTag.differentVariable
             */
            foreach ($match['action'] as $key => $action) {
                if ($action === $actionKey) {
                    /**
                     * @var non-empty-string
                     */
                    return trim($match['query'][$key]);
                }
            }
        } elseif ($actionKey === 'up') {
            return $queryString;
        }

        return null;
    }
}
