<?php

declare(strict_types=1);

namespace App\Console;

use App\Messenger\CommandBus;
use SocialBulletin\Core\Application\Place\Command\ImportPlacesCommand;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:places:import', 'Replace the place lookup data with a newline-delimited GeoJSON file (ADR-0024)')]
final readonly class ImportPlacesConsoleCommand
{
    private const string MEMORY_LIMIT = '512M';

    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    public function __invoke(
        #[Argument('A local path or an HTTPS URL to one GeoJSON feature per line')]
        string $source,
        OutputInterface $output,
    ): int {
        // One boundary with a detailed coastline (Highland: 8.4 MB of GeoJSON) decodes to more than
        // PHP's default 128 MB; the full Spain and UK import peaks near 160 MB (ADR-0024).
        ini_set('memory_limit', self::MEMORY_LIMIT);
        $started = hrtime(true);

        $this->commandBus->dispatch(ImportPlacesCommand::fromSource($source));

        $output->writeln(sprintf('Places imported in %.1f seconds.', (hrtime(true) - $started) / 1e9));

        return Command::SUCCESS;
    }
}
