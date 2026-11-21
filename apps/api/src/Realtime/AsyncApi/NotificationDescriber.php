<?php

declare(strict_types=1);

namespace App\Realtime\AsyncApi;

use SocialBulletin\Core\Application\Helper\Notification;
use Webmozart\Assert\Assert;

/**
 * Derives the AsyncAPI channel, operation, message and payload schema of one notification from its class,
 * and takes only the prose from the hand-written `config/asyncapi.yaml`.
 *
 * The channel address is the notification's own `channelFor()`, called with one `{placeholder}` per parameter.
 */
final readonly class NotificationDescriber
{
    /**
     * @param class-string<Notification> $notification
     * @param array<string, mixed>       $config
     *
     * @return array{channel: array{string, array<string, mixed>}, operation: array{string, array<string, mixed>}, message: array{string, array<string, mixed>}, schema: array{string, array<string, mixed>}}
     */
    public function describe(string $notification, array $config): array
    {
        $channelFor = [$notification, 'channelFor'];
        Assert::isCallable($channelFor, sprintf('%s needs a static channelFor() to be addressed.', $notification));

        $name = (new \ReflectionClass($notification))->getShortName();
        $channel = $config['channel'];
        $parameters = array_map(
            static fn (string $description): array => [
                'description' => $description,
            ],
            $config['parameters'],
        );

        return [
            'channel' => [
                $channel,
                [
                    'address' => $channelFor(
                        ...array_map(static fn (string $parameter): string => '{'.$parameter.'}', array_keys($parameters)),
                    ),
                    'description' => $config['description'],
                    'parameters' => $parameters,
                    'messages' => [
                        lcfirst($name) => [
                            '$ref' => '#/components/messages/'.$name,
                        ],
                    ],
                ],
            ],
            'operation' => [
                'receive'.$name,
                [
                    'action' => 'receive',
                    'summary' => $config['summary'],
                    'channel' => [
                        '$ref' => '#/channels/'.$channel,
                    ],
                    'security' => array_map(
                        static fn (string $scheme): array => [
                            '$ref' => '#/components/securitySchemes/'.$scheme,
                        ],
                        $config['security'],
                    ),
                    'messages' => [
                        [
                            '$ref' => '#/channels/'.$channel.'/messages/'.lcfirst($name),
                        ],
                    ],
                ],
            ],
            'message' => [
                $name,
                [
                    'name' => $name,
                    'summary' => $config['message'],
                    'contentType' => 'application/json',
                    'payload' => [
                        '$ref' => '#/components/schemas/'.$name,
                    ],
                ],
            ],
            'schema' => [$name, $this->schema($notification)],
        ];
    }

    /**
     * @param class-string<Notification> $notification
     *
     * @return array<string, mixed>
     */
    private function schema(string $notification): array
    {
        $types = [];
        $sample = [];
        foreach ((new \ReflectionClass($notification))->getConstructor()?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();
            Assert::isInstanceOf($type, \ReflectionNamedType::class, sprintf('%s::$%s needs a single declared type.', $notification, $parameter->getName()));

            $types[$parameter->getName()] = $type->getName();
            $sample[$parameter->getName()] = is_a($type->getName(), \BackedEnum::class, true) ? $type->getName()::cases()[0] : 'sample';
        }

        $properties = [];
        foreach (array_keys((new $notification(...$sample))->payload()) as $property) {
            $type = $types[$property] ?? throw new \LogicException(sprintf('%s publishes "%s", which is not one of its constructor arguments.', $notification, $property));

            if (is_a($type, \BackedEnum::class, true)) {
                $properties[$property] = [
                    'type' => 'string',
                    'enum' => array_map(static fn (\BackedEnum $case): string|int => $case->value, $type::cases()),
                ];

                continue;
            }

            Assert::same($type, 'string', sprintf('%s::$%s must be a string or a backed enum to be described.', $notification, $property));
            $properties[$property] = [
                'type' => 'string',
            ];
        }

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => array_keys($properties),
            'properties' => $properties,
        ];
    }
}
