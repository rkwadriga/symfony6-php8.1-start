<?php declare(strict_types=1);
/**
 * Created 2021-12-03
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Security\Authenticators;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class LoginAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private PasswordHasherFactoryInterface $encoder,
        private SerializerInterface $serializer,
        private UserProviderInterface $userProvider,
        private string $loginUrl,
        private string $loginParam,
        private string $passwordParam
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->get('_route') === $this->loginUrl;
    }

    public function authenticate(Request $request): Passport
    {
        $params = json_decode($request->getContent(), true);
        if (!is_array($params)) {
            throw new CustomUserMessageAuthenticationException('Invalid request');
        }

        if (!isset($params[$this->loginParam]) || !isset($params[$this->passwordParam])) {
            throw new CustomUserMessageAuthenticationException("Params \"{$this->loginParam}\" and \"{$this->passwordParam}\" are required");
        }

        $userBridge = new UserBadge($this->loginParam, function () use ($params): ?UserInterface {
            return $this->userProvider->loadUserByIdentifier($params[$this->loginParam]);
        });

        $user = $userBridge->getUser();
        if ($user === null ||
            !$this->encoder->getPasswordHasher($user)->verify($user->getPassword(), $params[$this->passwordParam])
        ) {
            throw new CustomUserMessageAuthenticationException("Invalid \"{$this->loginParam}\" or \"{$this->passwordParam}\"");
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