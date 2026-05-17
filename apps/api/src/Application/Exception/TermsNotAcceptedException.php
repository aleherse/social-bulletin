<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Application\Exception;

final class TermsNotAcceptedException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Terms and conditions must be accepted to register.');
    }
}
