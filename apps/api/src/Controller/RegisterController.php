<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/register', name: 'api_register', methods: ['POST'])]
final class RegisterController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly ValidatorInterface $validator,
        private readonly string $schema,
    ) {}

    public function __invoke(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        $violations = $this->validator->validate($email, [
            new Assert\NotBlank(),
            new Assert\Email(),
        ]);

        if (count($violations) > 0) {
            return new JsonResponse(['error' => (string) $violations->get(0)->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $row = $this->connection->fetchAssociative(
            "SELECT id, email FROM {$this->schema}.users WHERE email = :email",
            ['email' => $email],
        );

        if ($row === false) {
            $id = Uuid::v7()->toRfc4122();
            $this->connection->executeStatement(
                "INSERT INTO {$this->schema}.users (id, email, created_at) VALUES (:id, :email, NOW())",
                ['id' => $id, 'email' => $email],
            );
            $row = ['id' => $id, 'email' => $email];
        }

        $user = new User($row['id'], $row['email']);
        $token = $this->jwtManager->create($user);

        $cookie = Cookie::create('token')
            ->withValue($token)
            ->withExpires(new \DateTimeImmutable('+1 hour'))
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);

        $response = new JsonResponse(['email' => $user->getEmail()]);
        $response->headers->setCookie($cookie);

        return $response;
    }
}
