<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\example\presentation;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use kuaukutsu\poc\migration\MigratorArgs;

trait CommandOptions
{
    /**
     * @throws InvalidArgumentException
     */
    protected function getArguments(InputInterface $input): MigratorArgs
    {
        return new MigratorArgs(
            limit: $this->getOptionLimit($input),
            dryRun: $this->getOptionDryRun($input),
            dbName: $this->getOptionDbName($input),
        );
    }

    /**
     * @return non-negative-int
     * @throws InvalidArgumentException
     */
    private function getOptionLimit(InputInterface $input): int
    {
        /** @phpstan-ignore cast.int */
        $value = (int)$input->getOption('limit');
        if ($value < 0) {
            throw new InvalidArgumentException('Argument (limit) must be greater than to 0.');
        }

        return $value;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getOptionDryRun(InputInterface $input): bool
    {
        return $input->getOption('dry-run') === true;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getOptionDbName(InputInterface $input): ?string
    {
        $value = $input->getOption('db');
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return null;
    }
}
