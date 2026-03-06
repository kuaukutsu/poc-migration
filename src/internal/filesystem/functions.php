<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\filesystem;

/**
 * @param non-empty-string $path
 * @return non-empty-string
 * @infection-ignore-all
 */
function normalizePath(string $path): string
{
    return rtrim(trim($path), '/') . '/';
}

/**
 * @param non-empty-string $path
 * @param non-empty-string $postfix
 * @return non-empty-string
 * @infection-ignore-all
 */
function joinBasename(string $path, string $postfix): string
{
    return rtrim(trim($path), '/') . rtrim($postfix, '/') . '/';
}

/**
 * @param non-empty-string $path
 * @param non-empty-string $filename
 * @return non-empty-string
 * @infection-ignore-all
 */
function joinFile(string $path, string $filename): string
{
    return rtrim(trim($path), '/') . '/' . $filename;
}
