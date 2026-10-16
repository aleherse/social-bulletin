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
        // Users and movements are scenario-specific, so scenarios seed their
        // own through application code. The only shared data is reference
        // data: the categories the migrations seed and the fixture places
        // fixtures.feature imports.
    }

    #[Then('the kernel environment should be :environment')]
    public function theKernelEnvironmentShouldBe(string $environment): void
    {
        Assert::same($this->kernel->getEnvironment(), $environment);
    }
}
