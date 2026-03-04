<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\command;

use Throwable;
use kuaukutsu\poc\migration\internal\command;
use kuaukutsu\poc\migration\Context;

interface CommandInterface
{
    /**
     * @return list<non-empty-string>
     */
    public function fetchSavedMigrationNames(command\Args $args = new command\Args()): array;

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
