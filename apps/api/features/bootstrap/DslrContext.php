<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Symfony\Component\Process\Process;

final class DslrContext implements Context
{
    /** @BeforeScenario */
    public function restoreFixtureSnapshot(BeforeScenarioScope $scope): void
    {
        if ($scope->getScenario()->hasTag('fixtures') || $scope->getFeature()->hasTag('fixtures')) {
            return;
        }

        $process = new Process(['dslr', 'restore', 'fixtures']);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput() ?: $process->getOutput());
        }
    }
}
