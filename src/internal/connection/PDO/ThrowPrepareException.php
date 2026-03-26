<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\connection\PDO;

use PDO;
use kuaukutsu\poc\migration\exception\PrepareException;

/**
 * @psalm-internal kuaukutsu\poc\migration\internal\connection\PDO
 */
trait ThrowPrepareException
{
    /**
     * @throws PrepareException
     */
    private function prepareException(PDO $connection): never
    {
        /**
         * @var array{0: string, 1: int, 2: string} $info PDO::errorInfo Spec
         */
        $info = $connection->errorInfo();
        throw new PrepareException(
            (string)new ErrorInfo($info)
        );
    }
}
