<?php

declare(strict_types=1);

namespace SocialBulletin\Api\Controller;

use OpenApi\Attributes as OA;
use SocialBulletin\Api\Application\Command\RegisterUserCommand;
use SocialBulletin\Api\Application\Exception\DuplicateEmailException;
use SocialBulletin\Api\Application\Exception\TermsNotAcceptedException;
use SocialBulletin\Api\Application\UseCase\RegisterUserUseCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: 'Registration')]
final class RegistrationController
{
    public function __construct(
        private readonly RegisterUserUseCase $registerUser,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/register',
        summary: 'Register a new user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'termsAccepted'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string'),
                    new OA\Property(property: 'termsAccepted', type: 'boolean'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'User registered'),
            new OA\Response(response: 409, description: 'Email already registered'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(
                ['errors' => [['field' => 'body', 'message' => 'Invalid JSON.']]],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $violations = $this->validator->validate($data, new Assert\Collection([
            'email'         => [new Assert\NotBlank(), new Assert\Email()],
            'password'      => [new Assert\NotBlank(), new Assert\PasswordStrength(minScore: Assert\PasswordStrength::STRENGTH_MEDIUM)],
            'termsAccepted' => [new Assert\NotNull(), new Assert\Type('bool')],
        ]));

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $field = ltrim((string) $violation->getPropertyPath(), '[');
                $field = rtrim($field, ']');
                $errors[] = ['field' => $field, 'message' => $violation->getMessage()];
            }

            return new JsonResponse(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $command = new RegisterUserCommand(
                email: (string) ($data['email'] ?? ''),
                rawPassword: (string) ($data['password'] ?? ''),
                termsAccepted: (bool) ($data['termsAccepted'] ?? false),
            );

            $token  = $this->registerUser->execute($command);
            $userId = $this->extractUserIdFromToken($token);

            $response = new JsonResponse(['userId' => $userId], Response::HTTP_CREATED);
            $response->headers->setCookie(
                Cookie::create('token')
                    ->withValue($token)
                    ->withExpires(0)
                    ->withPath('/')
                    ->withSecure(true)
                    ->withHttpOnly(true)
                    ->withSameSite(Cookie::SAMESITE_STRICT)
            );

            return $response;
        } catch (DuplicateEmailException) {
            return new JsonResponse(
                ['error' => 'email_already_registered'],
                Response::HTTP_CONFLICT
            );
        } catch (TermsNotAcceptedException) {
            return new JsonResponse(
                ['errors' => [['field' => 'termsAccepted', 'message' => 'You must accept the terms and conditions.']]],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function extractUserIdFromToken(string $token): string
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return '';
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        return is_array($payload) ? (string) ($payload['sub'] ?? '') : '';
    }
}
