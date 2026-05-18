<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Hook\BeforeScenario;
use Doctrine\DBAL\Connection;
use RuntimeException;

final class DatabaseContext implements Context
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * ADR-0015: every scenario starts from the DSLR `fixtures` snapshot that
     * `make db` created. The @fixtures seeding scenarios are excluded because
     * they run before the first snapshot exists.
     */
    #[BeforeScenario('~@fixtures')]
    public function restoreDatabaseSnapshot(): void
    {
        // DSLR recreates the database, so any open connection must go first.
        $this->connection->close();

        exec('dslr --url "$DSLR_DATABASE_URL" restore fixtures 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException(sprintf(
                "Could not restore the DSLR 'fixtures' snapshot (did you run `make db`?):\n%s",
                implode("\n", $output),
            ));
        }
    }
}
