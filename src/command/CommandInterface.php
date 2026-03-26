<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\command;

use Throwable;
use kuaukutsu\poc\migration\Context;

interface CommandInterface
{
    /**
     * @return array<non-empty-string, non-negative-int>
     * @throws Throwable
     */
    public function fetchApplied(Options $options = new Options()): array;

    /**
     * @return bool true: request completed; false: request rejected
     * @throws Throwable
     */
    public function up(Context $context): bool;

    /**
     * @return bool true: request completed; false: request rejected
     * @throws Throwable
     */
    public function down(Context $context): bool;

    /**
     * @return bool true: request completed; false: request rejected
     * @throws Throwable
     */
    public function exec(Context $context): bool;
}
