<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\example\presentation;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use kuaukutsu\poc\migration\InputArgs;

trait CommandOptions
{
    /**
     * @throws InvalidArgumentException
     */
    protected function getArguments(InputInterface $input): InputArgs
    {
        $options = [];
        if ($input->hasOption('limit')) {
            $options['limit'] = $this->getOptionLimit($input);
        }

        if ($input->hasOption('dry-run')) {
            $options['dryRun'] = $this->getOptionDryRun($input);
        }

        if ($input->hasOption('db')) {
            $options['dbName'] = $this->getOptionDbName($input);
        }

        if ($input->hasOption('without-repeatable')) {
            $options['hasRepeatable'] = $this->getOptionWithoutRepeatable($input) === false; // inversion
        }

        return new InputArgs(...$options);
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
    private function getOptionWithoutRepeatable(InputInterface $input): bool
    {
        return $input->getOption('without-repeatable') === true;
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
