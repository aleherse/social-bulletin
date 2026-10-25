<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Http\Attribute\JsonApi as JsonApiAttribute;
use App\Http\JsonApi;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Renders what a `#[JsonApi]` controller returns (a model, or a list of them) as a JSON:API document.
 */
#[AsEventListener(event: KernelEvents::VIEW)]
final readonly class JsonApiViewListener
{
    public function __construct(
        private Manager $fractal,
    ) {
    }

    public function __invoke(ViewEvent $event): void
    {
        $jsonApi = $event->controllerArgumentsEvent?->getAttributes(JsonApiAttribute::class)[0] ?? null;

        if (null === $jsonApi) {
            return;
        }

        $result = $event->getControllerResult();
        $transformer = new $jsonApi->transformer();
        $resource = \is_array($result)
            ? new Collection($result, $transformer, $jsonApi->type)
            : new Item($result, $transformer, $jsonApi->type);

        $event->setResponse(new JsonResponse(
            $this->fractal->createData($resource)
                ->toArray(),
            $jsonApi->status,
            [
                'Content-Type' => JsonApi::CONTENT_TYPE,
            ],
        ));
    }
}
