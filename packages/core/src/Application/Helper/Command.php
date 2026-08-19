<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Helper;

use Webmozart\Assert\Assert;

/**
 * Base for the write use-case commands under `Core\Application\<Aggregate>`.
 *
 * A field the caller never supplied is left uninitialised rather than carrying a `null` that
 * would be indistinguishable from an explicit one, so `null` stays usable as a real value
 * (clearing a movement's location). `hasProperty()` is how a handler tells the two apart.
 */
abstract readonly class Command
{
    /**
     * Whether the caller supplied this field at all — `true` even when they supplied `null`.
     *
     * `isset()` cannot answer this: it reports `false` for an explicit `null`.
     */
    public function hasProperty(string $name): bool
    {
        Assert::propertyExists($this, $name);

        return (new \ReflectionProperty($this, $name))->isInitialized($this);
    }
}
