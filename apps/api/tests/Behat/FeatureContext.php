<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Tests\Behat;

use Behat\Behat\Context\Context;
use RuntimeException;
use SocialBulletin\Api\Kernel;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

final class FeatureContext implements Context
{
    private KernelBrowser $client;
    private ?Response $response = null;

    public function __construct()
    {
        $this->client = new KernelBrowser(new Kernel('test', true));
    }

    /**
     * @When I request :path
     */
    public function iRequest(string $path): void
    {
        $this->client->request('GET', $path);
        $this->response = $this->client->getResponse();
    }

    /**
     * @Then the response status code should be :statusCode
     */
    public function theResponseStatusCodeShouldBe(int $statusCode): void
    {
        $response = $this->requireResponse();

        if ($response->getStatusCode() !== $statusCode) {
            throw new RuntimeException(sprintf('Expected status code %d, got %d.', $statusCode, $response->getStatusCode()));
        }
    }

    /**
     * @Then the JSON response should contain :key with :value
     */
    public function theJsonResponseShouldContainWith(string $key, string $value): void
    {
        $payload = json_decode($this->requireResponse()->getContent(), true);

        if (!is_array($payload) || ($payload[$key] ?? null) !== $value) {
            throw new RuntimeException(sprintf('Expected JSON key "%s" to contain "%s".', $key, $value));
        }
    }

    private function requireResponse(): Response
    {
        if (!$this->response instanceof Response) {
            throw new RuntimeException('No response has been received.');
        }

        return $this->response;
    }
}
