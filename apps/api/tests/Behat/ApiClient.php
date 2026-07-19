<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Webmozart\Assert\Assert;

/**
 * HTTP machinery shared by all Behat contexts in a scenario: one cookie
 * jar and the latest response. The kernel (and with it this service) is
 * rebooted between scenarios, so state never leaks across them.
 */
final class ApiClient
{
    private ?Response $response = null;

    /** @var array<string, string> Cookie jar carried across requests in one scenario. */
    private array $cookies = [];

    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
    }

    public function request(string $method, string $path, ?string $body = null): void
    {
        $request = Request::create(
            $path,
            $method,
            [],
            $this->cookies,
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $body,
        );

        $this->response = $this->kernel->handle($request);
        $this->storeResponseCookies();
    }

    public function response(): Response
    {
        Assert::notNull($this->response, 'No request has been sent yet.');

        return $this->response;
    }

    /**
     * @return array<string, mixed>
     */
    public function decodedResponse(): array
    {
        $content = $this->response()->getContent();

        Assert::string($content);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }

    public function findResponseCookie(string $name): ?Cookie
    {
        foreach ($this->response()->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $name) {
                return $cookie;
            }
        }

        return null;
    }

    private function storeResponseCookies(): void
    {
        foreach ($this->response()->headers->getCookies() as $cookie) {
            $value = $cookie->getValue();
            $expired = $cookie->getExpiresTime() !== 0 && $cookie->getExpiresTime() < time();

            if ($value === null || $value === '' || $expired) {
                unset($this->cookies[$cookie->getName()]);

                continue;
            }

            $this->cookies[$cookie->getName()] = $value;
        }
    }
}
