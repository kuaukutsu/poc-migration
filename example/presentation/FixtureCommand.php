<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\example\presentation;

use Throwable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use kuaukutsu\poc\migration\MigratorInterface;

#[AsCommand(
    name: 'migrate:fixture',
    description: 'Fixture',
)]
final class FixtureCommand extends Command
{
    /**
     * @throws LogicException
     */
    public function __construct(private readonly MigratorInterface $migrator)
    {
        parent::__construct();
    }

    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->migrator->fixture();
        } catch (Throwable) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
