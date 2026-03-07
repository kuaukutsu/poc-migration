<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\internal\connection\PDO;

enum Type
{
    case PDO_PGSQL;

    case PDO_MYSQL;

    case PDO_SQLITE;

    case UNSUPPORTED;

    /**
     * @return non-empty-lowercase-string
     */
    public function value(): string
    {
        if ($this === self::UNSUPPORTED) {
            return 'unsupported';
        }

        [, $db] = explode('_', $this->name);

        /**
         * @var non-empty-lowercase-string
         */
        return strtolower($db);
    }
}
