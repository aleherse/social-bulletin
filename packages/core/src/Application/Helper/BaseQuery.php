<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Helper;

/**
 * Base for the read use-case queries under `Core\Application\<Aggregate>\Query`.
 *
 * The template parameter is what handling the query produces. A query declares it with
 * `@extends BaseQuery<…>`, which is what lets `App\Messenger\QueryBus::dispatch()`
 * hand a caller a typed result instead of `mixed`.
 *
 * @template-covariant TResult
 */
abstract readonly class BaseQuery
{
}
