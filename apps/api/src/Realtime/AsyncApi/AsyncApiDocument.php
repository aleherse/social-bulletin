<?php

declare(strict_types=1);

namespace App\Realtime\AsyncApi;

use App\Realtime\TicketIssuer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

/**
 * The AsyncAPI description of what the API pushes to browsers (ADR-0024).
 *
 * The hand-written parts come from `config/asyncapi.yaml`; every notification it lists is described
 * from its class by {@see NotificationDescriber}. `make asyncapi-check` compares the dump with the committed file.
 */
final readonly class AsyncApiDocument
{
    public function __construct(
        private NotificationDescriber $describer,
        #[Autowire('%kernel.project_dir%/config/asyncapi.yaml')]
        private string $configFile,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        /** @var array{document: array<string, mixed>, notifications: array<class-string<\SocialBulletin\Core\Application\Helper\Notification>, array<string, mixed>>} $config */
        $config = Yaml::parseFile($this->configFile);
        $document = $config['document'];

        $channels = $operations = $messages = $schemas = [];
        foreach ($config['notifications'] as $notification => $notificationConfig) {
            $description = $this->describer->describe($notification, $notificationConfig);

            $channels[$description['channel'][0]] = $description['channel'][1];
            $operations[$description['operation'][0]] = $description['operation'][1];
            $messages[$description['message'][0]] = $description['message'][1];
            $schemas[$description['schema'][0]] = $description['schema'][1];
        }

        $components = $document['components'];

        return $this->withPlaceholders([
            'asyncapi' => $document['asyncapi'],
            'info' => $document['info'],
            'channels' => $channels,
            'operations' => $operations,
            'components' => [
                'messages' => $messages,
                'schemas' => $schemas,
            ] + $components,
        ]);
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return array<string, mixed>
     */
    private function withPlaceholders(array $document): array
    {
        $values = [
            '%ticket_lifetime%' => (string) TicketIssuer::LIFETIME_SECONDS,
            '%ticket_audience%' => TicketIssuer::AUDIENCE,
        ];

        array_walk_recursive($document, static function (mixed &$value) use ($values): void {
            if (\is_string($value)) {
                $value = strtr($value, $values);
            }
        });

        return $document;
    }
}
