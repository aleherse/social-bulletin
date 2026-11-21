<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Messenger\CommandBus;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use SocialBulletin\Core\Application\Place\Command\ImportPlacesCommand;
use Symfony\Component\HttpKernel\KernelInterface;
use Webmozart\Assert\Assert;

final class PlaceContext implements Context
{
    public function __construct(
        private readonly ApiClient $apiClient,
        private readonly CommandBus $commandBus,
        private readonly KernelInterface $kernel,
    ) {
    }

    #[Given('the places in :file are imported')]
    public function thePlacesInAreImported(string $file): void
    {
        $this->commandBus->dispatch(ImportPlacesCommand::fromSource($this->featuresPath($file)));
    }

    #[Then('the response body should not contain :text')]
    public function theResponseBodyShouldNotContain(string $text): void
    {
        Assert::notContains((string) $this->apiClient->response()->getContent(), $text);
    }

    private function featuresPath(string $file): string
    {
        return $this->kernel->getProjectDir().'/features/'.$file;
    }
}
