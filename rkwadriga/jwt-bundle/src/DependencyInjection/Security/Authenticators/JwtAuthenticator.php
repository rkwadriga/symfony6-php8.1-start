<?php declare(strict_types=1);
/**
 * Created 2021-12-03
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Security\Authenticators;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Rkwadriga\JwtBundle\DependencyInjection\Services\TokenIdentifier;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private TokenIdentifier $identifier,
        private string $loginUrl
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->get('_route') !== $this->loginUrl;
    }

    public function authenticate(Request $request): Passport
    {
        dd($this->identifier->identify($request));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        dd($request, $token, $firewallName);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        dd($request, $exception);
    }
}