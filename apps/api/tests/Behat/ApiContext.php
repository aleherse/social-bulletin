<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Symfony\Component\HttpKernel\KernelInterface;
use Webmozart\Assert\Assert;

final class ApiContext implements Context
{
    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
    }

    #[Given('the baseline dataset is loaded')]
    public function theBaselineDatasetIsLoaded(): void
    {
        // The walking-skeleton slice needs no shared baseline data: session
        // scenarios seed their own users through application code. The empty
        // baseline is still snapshotted so restores are exercised end to end.
    }

    #[Then('the kernel environment should be :environment')]
    public function theKernelEnvironmentShouldBe(string $environment): void
    {
        Assert::same($this->kernel->getEnvironment(), $environment);
    }
}
