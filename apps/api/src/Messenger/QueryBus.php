<?php

declare(strict_types=1);

namespace App\Messenger;

use SocialBulletin\Core\Application\Helper\BaseQuery;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class QueryBus
{
    use HandleTrait;

    public function __construct(MessageBusInterface $queryBus)
    {
        $this->messageBus = $queryBus;
    }

    /**
     * @template TResult
     *
     * @param BaseQuery<TResult> $query
     *
     * @return TResult
     */
    public function dispatch(BaseQuery $query): mixed
    {
        try {
            /** @var TResult $result */
            $result = $this->handle($query);
        } catch (HandlerFailedException $exception) {
            throw $exception->getPrevious() ?? $exception;
        }

        return $result;
    }
}
