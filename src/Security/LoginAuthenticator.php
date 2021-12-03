<?php

namespace App\Security;

use App\Api\Routes;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Serializer\SerializerInterface;

class LoginAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private EntityManagerInterface $em,
        private PasswordHasherFactoryInterface $encoder,
        private SerializerInterface $serializer
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->get('_route') === Routes::LOGIN;
    }

    public function authenticate(Request $request): Passport
    {
        $params = json_decode($request->getContent(), true);
        if (!is_array($params)) {
            throw new CustomUserMessageAuthenticationException('Invalid request');
        }

        if (!isset($params['email']) || !isset($params['password'])) {
            throw new CustomUserMessageAuthenticationException('Params "login" and "password" are required');
        }

        $repository = $this->em->getRepository(User::class);
        $userBridge = new UserBadge('email', function (string $identifier) use ($params, $repository): ?User {
            return $repository->findOneBy(['email' => $params['email']]);
        });

        $user = $userBridge->getUser();
        if ($user === null ||
            !$this->encoder->getPasswordHasher($user)->verify($user->getPassword(), $params['password'])
        ) {
            throw new CustomUserMessageAuthenticationException('Invalid email or password');
        }

        return new SelfValidatingPassport($userBridge);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $json = $this->serializer->serialize($token->getUser(), 'json', [
            'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
        ]);
        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'code' => $exception->getCode(),
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }
}
