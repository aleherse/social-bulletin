<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

final class RecordingHttpClient implements HttpClientInterface
{
    /**
     * @var list<array{method: string, url: string, headers: list<string>, body: string}>
     */
    private array $requests = [];

    private readonly MockHttpClient $inner;

    public function __construct()
    {
        $this->inner = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            $this->requests[] = [
                'method' => $method,
                'url' => $url,
                'headers' => array_values(array_merge([], ...array_values($options['normalized_headers']))),
                'body' => (string) $options['body'],
            ];

            return new MockResponse('{"successful":[],"failed":[]}');
        });
    }

    /**
     * @return list<array{method: string, url: string, headers: list<string>, body: string}>
     */
    public function requests(): array
    {
        return $this->requests;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->inner->request($method, $url, $options);
    }

    public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
    {
        return $this->inner->stream($responses, $timeout);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function withOptions(array $options): static
    {
        return $this;
    }
}
