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
    /**
     * @var non-empty-string
     */
    private string $path;

    /**
     * @param non-empty-string $path
     */
    public function __construct(string $path)
    {
        $this->path = normalizePath($path);
    }

    /**
     * @param array<non-empty-string, int> $listExcluded
     * @return Iterator<non-empty-string, non-empty-string>
     * @throws ConfigurationException
     */
    public function up(array $listExcluded, Args $args = new Args()): Iterator
    {
        $iternum = 0;
        foreach ($this->makeIterator($this->path) as $matchFilename) {
            $filepath = $matchFilename[0];
            $filename = basename($filepath);
            if (isset($listExcluded[$filename])) {
                continue;
            }

            if ($args->limit > 0 && $iternum >= $args->limit) {
                return;
            }

            $command = $this->prepareCommand($filepath, 'up');
            if ($command !== null) {
                $iternum++;

                /** @phpstan-ignore generator.keyType */
                yield $filename => $command;
            }
        }
    }

    /**
     * @param array<non-empty-string, int> $listApplied
     * @return Iterator<non-empty-string, non-empty-string>
     * @throws ConfigurationException
     */
    public function down(array $listApplied, Args $args = new Args()): Iterator
    {
        $iternum = 0;
        foreach ($listApplied as $filename => $_) {
            if ($args->limit > 0 && $iternum >= $args->limit) {
                return;
            }

            $command = $this->prepareCommand($this->path . $filename, 'down');
            if ($command !== null) {
                $iternum++;

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
        $iternum = 0;
        foreach ($this->makeIterator(joinBasename($this->path, '-fixture')) as $matchFilename) {
            if ($args->limit > 0 && $iternum >= $args->limit) {
                return;
            }

            $filepath = $matchFilename[0];
            $command = $this->prepareCommand($filepath, 'up');
            if ($command !== null) {
                $iternum++;

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
        foreach ($this->makeIterator(joinBasename($this->path, '-repeatable')) as $matchFilename) {
            $filepath = $matchFilename[0];
            $command = $this->prepareCommand($filepath, 'up');
            if ($command !== null) {
                /** @phpstan-ignore generator.keyType */
                yield basename($filepath) => $command;
            }
        }
    }

    /**
     * @param non-empty-string $filename
     * @return non-empty-string filepath
     * @throws ConfigurationException
     */
    public function create(string $filename, string $body): string
    {
        if (file_exists($this->path) === false) {
            throw new ConfigurationException(
                sprintf('the dir [%s] is not exists.', $this->path)
            );
        }

        $filepath = joinFile($this->path, $filename);
        if (file_exists($filepath)) {
            throw new ConfigurationException(
                sprintf('the file [%s] is exists.', $filepath)
            );
        }

        if (file_put_contents($filepath, $body) !== false) {
            return $filepath;
        }

        throw new ConfigurationException(
            sprintf('the file [%s] is not saved.', $filepath)
        );
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
                '/.+(?<!skip)\.sql/i',
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

        if (preg_match_all('/^--\s?@(?<action>\w+)\s?\R(?<query>(?:(?!^--\s?@).)*)/ms', $queryString, $match) > 0) {
            /**
             * @var array{"action": non-empty-string[], "query": string[]} $match
             * @phpstan-ignore varTag.nativeType
             */
            $key = array_search($actionKey, $match['action'], true);
            if ($key !== false) {
                $query = $match['query'][$key];
                return $query === '' ? null : $query;
            }
        } elseif ($actionKey === 'up') {
            return $queryString;
        }

        return null;
    }
}
