<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Kernel;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Doctrine\DBAL\Connection;
use JmesPath\Env as JmesPath;
use Symfony\Component\HttpKernel\KernelInterface;
use Webmozart\Assert\Assert;

final class ApiContext implements Context
{
    private ?KernelInterface $kernel = null;
    private ?string $responseContent = null;
    private int $responseStatus = 0;

    /** @var array<string, string> */
    private array $cookies = [];

    /** @Given the database is clean */
    public function theDatabaseIsClean(): void
    {
        $this->connection()->executeStatement('TRUNCATE bulletin.users RESTART IDENTITY CASCADE');
    }

    /** @When I send a POST request to :path with body: */
    public function iSendPostRequestWithBody(string $path, PyStringNode $body): void
    {
        $this->dispatch('POST', $path, $body->getRaw());
    }

    /** @When I send a GET request to :path */
    public function iSendGetRequest(string $path): void
    {
        $this->dispatch('GET', $path);
    }

    /** @Then the response status code should be :status */
    public function theResponseStatusCodeShouldBe(int $status): void
    {
        Assert::eq($this->responseStatus, $status);
    }

    /** @Then the JSON response should match: */
    public function theJsonResponseShouldMatch(PyStringNode $expression): void
    {
        $data = json_decode($this->responseContent ?? '', true, 512, JSON_THROW_ON_ERROR);
        $result = JmesPath::search($expression->getRaw(), $data);
        Assert::notNull($result);
    }

    private function dispatch(string $method, string $path, ?string $body = null): void
    {
        $kernel = $this->kernel();
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTPS' => 'on'];
        if ($this->cookies !== []) {
            $server['HTTP_COOKIE'] = implode('; ', array_map(
                static fn (string $name, string $value): string => sprintf('%s=%s', $name, $value),
                array_keys($this->cookies),
                array_values($this->cookies),
            ));
        }

        $request = \Symfony\Component\HttpFoundation\Request::create(
            $path,
            $method,
            [],
            $this->cookies,
            [],
            $server,
            $body,
        );
        $response = $kernel->handle($request);
        $this->responseStatus = $response->getStatusCode();
        $this->responseContent = $response->getContent();

        foreach ($response->headers->getCookies() as $cookie) {
            $this->cookies[$cookie->getName()] = $cookie->getValue();
        }

        $kernel->terminate($request, $response);
    }

    private function kernel(): KernelInterface
    {
        if ($this->kernel === null) {
            require_once dirname(__DIR__).'/bootstrap.php';
            $_SERVER['DEFAULT_URI'] ??= 'https://api.dev.social.aleherse.com';
            $this->kernel = new Kernel('test', true);
            $this->kernel->boot();
        }

        return $this->kernel;
    }

    private function connection(): Connection
    {
        return $this->kernel()->getContainer()->get('doctrine.dbal.default_connection');
    }
}
