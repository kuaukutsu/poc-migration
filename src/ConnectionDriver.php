<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration;

enum ConnectionDriver: string
{
    case PDO_PGSQL = 'pgsql';

    case PDO_MYSQL = 'mysql';

    case PDO_SQLITE = 'sqlite';

    case UNSUPPORTED = 'unsupported';
}
