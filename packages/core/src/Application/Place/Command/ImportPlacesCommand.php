<?php

declare(strict_types=1);

namespace SocialBulletin\Core\Application\Place\Command;

use SocialBulletin\Core\Application\Helper\BaseCommand;

/**
 * Handled by {@see ImportPlacesHandler}.
 */
final readonly class ImportPlacesCommand extends BaseCommand
{
    private function __construct(
        public string $source,
    ) {
    }

    /**
     * @param string $source a path or URL to one GeoJSON feature per line
     */
    public static function fromSource(string $source): self
    {
        return new self($source);
    }
}
