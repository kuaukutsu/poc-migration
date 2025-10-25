<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\event;

enum Event: string
{
    case ConnectionError = 'connection-error-event';

    case ConfigurationError = 'configuration-error-event';

    case FileError = 'file-error-event';

    case MigrateSuccess = 'migrate-success-event';

    case MigrateError = 'migrate-error-event';
}
