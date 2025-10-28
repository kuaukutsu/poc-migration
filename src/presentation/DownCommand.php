<?php

declare(strict_types=1);

namespace kuaukutsu\poc\migration\presentation;

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
use kuaukutsu\poc\migration\internal\MigrateArgs;
use kuaukutsu\poc\migration\Migrator;

#[AsCommand(
    name: 'migrate:down',
    description: 'Down migration',
)]
final class DownCommand extends Command
{
    /**
     * @throws LogicException
     */
    public function __construct(
        private readonly Migrator $migrator,
    ) {
        parent::__construct();
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Override]
    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Number of files processed');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->migrator->down($this->getArguments($input));
        } catch (InvalidArgumentException $e) {
            $output->writeln($e->getMessage());
            return Command::INVALID;
        } catch (InitializationException $e) {
            $output->writeln('Calling the command "migrate:init" may help fix the error.');
            $output->writeln($e->getMessage());
            return Command::INVALID;
        } catch (Throwable) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getArguments(InputInterface $input): MigrateArgs
    {
        /** @phpstan-ignore cast.int */
        $limit = (int)$input->getOption('limit');
        if ($limit === 0) {
            return new MigrateArgs();
        }

        if ($limit > 0) {
            return new MigrateArgs(limit: $limit);
        }

        throw new InvalidArgumentException('Argument (limit) must be greater than to 0.');
    }
}
