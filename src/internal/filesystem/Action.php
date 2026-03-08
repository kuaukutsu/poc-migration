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
    public function up(array $listExcluded, Options $options = new Options()): Iterator
    {
        $iternum = 0;
        foreach ($this->makeIterator($this->path) as $matchFilename) {
            $filepath = $matchFilename[0];
            $filename = basename($filepath);
            if (isset($listExcluded[$filename])) {
                continue;
            }

            if ($options->limit > 0 && $iternum >= $options->limit) {
                return;
            }

            $command = $this->prepareCommand($filepath, ActionType::UP);
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
    public function down(array $listApplied, Options $options = new Options()): Iterator
    {
        $iternum = 0;
        foreach ($listApplied as $filename => $_) {
            if ($options->limit > 0 && $iternum >= $options->limit) {
                return;
            }

            $command = $this->prepareCommand($this->path . $filename, ActionType::DOWN);
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
    public function fixture(Options $options = new Options()): Iterator
    {
        $iternum = 0;
        foreach ($this->makeIterator(joinBasename($this->path, '-fixture')) as $matchFilename) {
            if ($options->limit > 0 && $iternum >= $options->limit) {
                return;
            }

            $filepath = $matchFilename[0];
            $command = $this->prepareCommand($filepath, ActionType::UP);
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
            $command = $this->prepareCommand($filepath, ActionType::UP);
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
        if (is_dir($this->path) === false || is_writable($this->path) === false) {
            throw new ConfigurationException(
                sprintf('the dir [%s] is not writable or does not exist.', $this->path)
            );
        }

        $filepath = joinFilename($this->path, $filename);
        if (file_exists($filepath)) {
            throw new ConfigurationException(
                sprintf('the file [%s] is exist.', $filepath)
            );
        }

        if (@file_put_contents($filepath, $body, LOCK_EX) === false) {
            throw new ConfigurationException(
                sprintf('the file [%s] is not saved.', $filepath)
            );
        }

        return $filepath;
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
     * @return non-empty-string|null
     * @throws ConfigurationException
     */
    private function prepareCommand(string $filepath, ActionType $action): ?string
    {
        if (is_readable($filepath) === false) {
            throw new ConfigurationException(
                sprintf('the file [%s] is not readable or does not exist.', $filepath)
            );
        }

        $content = @file_get_contents($filepath);
        if ($content === false) {
            throw new ConfigurationException(
                sprintf('failed to read file [%s].', $filepath)
            );
        }

        if ($content === '') {
            return null;
        }

        // ?: preg_split(...)
        if (preg_match_all('/^--\s?@(?<action>\w+)\s?\R(?<query>(?:(?!^--\s?@).)*)/ms', $content, $match) > 0) {
            /**
             * @var array{"action": non-empty-string[], "query": string[]} $match
             * @phpstan-ignore varTag.nativeType
             */
            $index = array_search($action->value, $match['action'], true);
            if ($index !== false) {
                $query = $match['query'][$index];
                return $query === '' ? null : $query;
            }

            return null;
        }

        if ($action === ActionType::UP) {
            return $content;
        }

        return null;
    }
}
