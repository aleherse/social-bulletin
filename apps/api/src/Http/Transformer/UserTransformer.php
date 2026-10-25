<?php

declare(strict_types=1);

namespace App\Http\Transformer;

use League\Fractal\TransformerAbstract;
use SocialBulletin\Core\Application\User\Model\User;

final class UserTransformer extends TransformerAbstract
{
    /**
     * @return array{id: string, email: string}
     */
    public function transform(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
        ];
    }
}
