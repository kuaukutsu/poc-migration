<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration;

use kuaukutsu\poc\migration\exception\ActionException;
use kuaukutsu\poc\migration\exception\ConfigurationException;
use kuaukutsu\poc\migration\exception\ConnectionException;
use kuaukutsu\poc\migration\exception\InitializationException;

interface MigratorInterface
{
    /**
     * @throws ActionException
     * @throws ConfigurationException If the driver is not implemented
     * @throws ConnectionException
     */
    public function init(): void;

    /**
     * @throws ActionException
     * @throws ConfigurationException If the driver is not implemented
     * @throws ConnectionException
     * @throws InitializationException
     */
    public function up(MigratorArgs $args = new MigratorArgs()): void;

    /**
     * @throws ActionException
     * @throws ConfigurationException If the driver is not implemented
     * @throws ConnectionException
     * @throws InitializationException
     */
    public function down(MigratorArgs $args = new MigratorArgs()): void;

    /**
     * @throws ActionException
     * @throws ConfigurationException If the driver is not implemented
     * @throws ConnectionException
     */
    public function fixture(MigratorArgs $args = new MigratorArgs()): void;
}
