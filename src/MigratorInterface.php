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
     * @throws InitializationException initialization step is required
     */
    public function up(InputArgs $args = new InputArgs()): void;

    /**
     * @throws ActionException
     * @throws ConfigurationException If the driver is not implemented
     * @throws ConnectionException
     * @throws InitializationException initialization step is required
     */
    public function down(InputArgs $args = new InputArgs()): void;

    /**
     * @throws ActionException
     * @throws ConfigurationException If the driver is not implemented
     * @throws ConnectionException
     * @throws InitializationException initialization step is required
     */
    public function redo(InputArgs $args = new InputArgs()): void;

    /**
     * @throws ActionException
     * @throws ConfigurationException If the driver is not implemented
     * @throws ConnectionException
     */
    public function fixture(InputArgs $args = new InputArgs()): void;
}
