<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Doctrine\DBAL\Connection;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * ADR-0013: every test starts from the DSLR `fixtures` snapshot that `make db` created.
 */
final class DatabaseSnapshot
{
    public static function restore(Connection $connection): void
    {
        // DSLR recreates the database, so any open connection must go first.
        $connection->close();

        $process = new Process(['dslr', '--url', (string) getenv('DSLR_DATABASE_URL'), 'restore', 'fixtures'], timeout: 60);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            throw new \RuntimeException(sprintf("Could not restore the DSLR 'fixtures' snapshot (did you run `make db`?):\n%s", $process->getErrorOutput().$process->getOutput()), 0, $exception);
        }
    }
}
