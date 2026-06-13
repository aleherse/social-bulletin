<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Symfony\Component\Process\Process;

final class FeatureContext implements Context
{
    #[\Behat\Behat\Hook\Attribute\BeforeScenario]
    public function restoreFixtures(BeforeScenarioScope $scope): void
    {
        if ($scope->getScenario()->hasTag('fixtures')) {
            return;
        }

        $process = new Process(['dslr', 'restore', 'fixtures']);
        $process->run();
    }

    /** @Given the database has been restored from the fixtures snapshot */
    public function theDatabaseHasBeenRestoredFromTheFixturesSnapshot(): void
    {
        // Placeholder: snapshot restore is triggered via BeforeScenario hook.
    }
}
