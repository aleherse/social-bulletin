<?php

declare(strict_types=1);

namespace App\Realtime;

use AsyncAws\Core\Configuration;
use AsyncAws\Core\Credentials\ChainProvider;
use AsyncAws\Core\Credentials\CredentialProvider;
use AsyncAws\Core\Request;
use AsyncAws\Core\RequestContext;
use AsyncAws\Core\Signer\SignerV4;
use AsyncAws\Core\Stream\StringStream;
use SocialBulletin\Core\Application\Helper\Notification;
use SocialBulletin\Core\Application\Helper\NotificationPublisher;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Publishes to the AppSync Events HTTP endpoint (ADR-0024), signed with IAM SigV4.
 *
 * The local realtime container serves the same endpoint and ignores the signature,
 * so every environment takes this one code path.
 */
final readonly class AppSyncEventsPublisher implements NotificationPublisher
{
    private CredentialProvider $credentials;

    private SignerV4 $signer;

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%env(REALTIME_PUBLISH_URL)%')]
        private string $publishUrl,
        #[Autowire('%env(AWS_REGION)%')]
        string $region,
    ) {
        $this->credentials = ChainProvider::createDefaultChain($httpClient);
        $this->signer = new SignerV4('appsync', $region);
    }

    public function publish(Notification ...$notifications): void
    {
        foreach ($notifications as $notification) {
            $this->send($notification);
        }
    }

    private function send(Notification $notification): void
    {
        $body = json_encode([
            'channel' => $notification->channel(),
            'events' => [json_encode($notification->payload(), \JSON_THROW_ON_ERROR)],
        ], \JSON_THROW_ON_ERROR);

        $url = parse_url($this->publishUrl);
        $request = new Request('POST', $url['path'] ?? '/', [], [
            'Content-Type' => 'application/json',
        ], StringStream::create($body));
        $request->setEndpoint(sprintf('%s://%s%s', $url['scheme'] ?? 'https', $url['host'] ?? '', isset($url['port']) ? ':'.$url['port'] : ''));

        $credentials = $this->credentials->getCredentials(Configuration::create([]))
            ?? throw new PublishFailed('No AWS credentials are available to sign the realtime publish.');
        $this->signer->sign($request, $credentials, new RequestContext());

        $result = $this->httpClient->request('POST', $this->publishUrl, [
            'headers' => $request->getHeaders(),
            'body' => $body,
        ])->toArray();

        if ([] !== ($result['failed'] ?? [])) {
            throw new PublishFailed(sprintf('AppSync Events rejected the notification for "%s".', $notification->channel()));
        }
    }
}
