<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Messenger\CommandBus;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use SocialBulletin\Core\Application\Place\Command\ImportPlacesCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Webmozart\Assert\Assert;

final class PlaceContext implements Context
{
    private ?ApplicationTester $console = null;

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

    /**
     * The console command is the behaviour under test here, not a way of arranging state.
     */
    #[When('I run the console command :name with the file :file')]
    public function iRunTheConsoleCommandWithTheFile(string $name, string $file): void
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $this->console = new ApplicationTester($application);
        $this->console->run([
            'command' => $name,
            'source' => $this->featuresPath($file),
        ]);
    }

    #[Then('the console command should succeed')]
    public function theConsoleCommandShouldSucceed(): void
    {
        $console = $this->console();

        Assert::same($console->getStatusCode(), 0, $console->getDisplay());
    }

    #[Then('the console command should fail mentioning :text')]
    public function theConsoleCommandShouldFailMentioning(string $text): void
    {
        $console = $this->console();

        Assert::notSame($console->getStatusCode(), 0);
        Assert::contains($console->getDisplay(), $text);
    }

    #[Then('the response body should not contain :text')]
    public function theResponseBodyShouldNotContain(string $text): void
    {
        Assert::notContains((string) $this->apiClient->response()->getContent(), $text);
    }

    private function console(): ApplicationTester
    {
        Assert::notNull($this->console, 'No console command has been run yet.');

        return $this->console;
    }

    private function featuresPath(string $file): string
    {
        return $this->kernel->getProjectDir().'/features/'.$file;
    }
}
