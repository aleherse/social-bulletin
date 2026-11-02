<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Tests\Support\DatabaseSnapshot;
use Behat\Behat\Context\Context;
use Behat\Hook\BeforeScenario;
use Doctrine\DBAL\Connection;

final class DatabaseContext implements Context
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * ADR-0013: every scenario starts from the DSLR `fixtures` snapshot that
     * `make db` created. The @fixtures seeding scenarios are excluded because
     * they run before the first snapshot exists.
     */
    #[BeforeScenario('~@fixtures')]
    public function restoreDatabaseSnapshot(): void
    {
        DatabaseSnapshot::restore($this->connection);
    }
}
