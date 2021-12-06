<?php declare(strict_types=1);
/**
 * Created 2021-12-03
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Security\Authenticators;

use Rkwadriga\JwtBundle\Exceptions\TokenValidatorException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Rkwadriga\JwtBundle\DependencyInjection\Services\TokenIdentifier;
use Rkwadriga\JwtBundle\DependencyInjection\Services\TokenValidator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private TokenIdentifier $identifier,
        private TokenValidator $validator,
        private UserProviderInterface $userProvider,
        private string $loginUrl,
        private string $refreshUrl,
        private string $loginParam,
    ) {}

    public function supports(Request $request): ?bool
    {
        return !in_array($request->get('_route'), [$this->loginUrl, $this->refreshUrl]);
    }

    public function authenticate(Request $request): Passport
    {
        [$accessTokenData, $refreshTokenData] = $this->identifier->identify($request);
        // Refresh token allowed only in "refresh" request
        if ($refreshTokenData !== null) {
            throw new AuthenticationException('Refresh token is not allowed in this request');
        }

        $this->validator->validateExpiredAt($accessTokenData, AuthenticationException::class);
        $this->validator->validatePayload($accessTokenData, [$this->loginParam], AuthenticationException::class);

        $userIdentifierValue = $accessTokenData->getPayload()[$this->loginParam];
        $userBridge = new UserBadge($this->loginParam, function () use ($userIdentifierValue): ?UserInterface {
            return $this->userProvider->loadUserByIdentifier($userIdentifierValue);
        });

        if ($userBridge->getUser() === null) {
            throw new AuthenticationException('Invalid access token');
        }

        return new SelfValidatingPassport($userBridge);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = $exception->getPrevious() instanceof TokenValidatorException
            ? $exception->getMessage()
            : strtr($exception->getMessageKey(), $exception->getMessageData());

        $data = [
            'code' => $exception->getCode(),
            'message' => $message,
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }
}