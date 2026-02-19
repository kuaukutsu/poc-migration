<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\driver;

enum DriverType
{
    case PDO_PGSQL;

    case PDO_MYSQL;

    case PDO_SQLITE;

    case AMPHP_PGSQL;

    case AMPHP_MYSQL;

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
