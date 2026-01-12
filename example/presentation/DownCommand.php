<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\example\presentation;

use Override;
use Throwable;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use kuaukutsu\poc\migration\exception\InitializationException;
use kuaukutsu\poc\migration\exception\MigratorException;
use kuaukutsu\poc\migration\MigratorInterface;

#[AsCommand(
    name: 'migrate:down',
    description: 'Down migration',
)]
final class DownCommand extends Command
{
    use CommandOptions;

    /**
     * @throws LogicException
     */
    public function __construct(private readonly MigratorInterface $migrator)
    {
        parent::__construct();
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Override]
    protected function configure(): void
    {
        $this->addOption('db', null, InputOption::VALUE_OPTIONAL, 'Name database');
        $this->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Number of files processed');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->migrator->down($this->getArguments($input));
        } catch (InvalidArgumentException | MigratorException $e) {
            if ($e instanceof InitializationException) {
                $output->writeln('Calling the command "migrate:init" may help fix the error.');
            }
            $output->writeln($e->getMessage());
            return Command::INVALID;
        } catch (Throwable) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
