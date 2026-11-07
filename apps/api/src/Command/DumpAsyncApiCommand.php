<?php

declare(strict_types=1);

namespace App\Command;

use App\Realtime\AsyncApi\AsyncApiDocument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:asyncapi:dump', description: 'Print the AsyncAPI document of the realtime notifications as JSON')]
final class DumpAsyncApiCommand extends Command
{
    public function __construct(
        private readonly AsyncApiDocument $document,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(json_encode(
            $this->document->build(),
            \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR,
        ));

        return Command::SUCCESS;
    }
}
