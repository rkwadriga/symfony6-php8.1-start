<?php declare(strict_types=1);
/**
 * Created 2021-12-17
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Authenticator;

use Exception;
use Rkwadriga\JwtBundle\DependencyInjection\TokenGeneratorInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenIdentifierInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\DependencyInjection\TokenValidatorInterface;
use Rkwadriga\JwtBundle\Enum\AuthenticationType;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Event\AuthenticationFinishedSuccessful;
use Rkwadriga\JwtBundle\Event\AuthenticationFinishedUnsuccessful;
use Rkwadriga\JwtBundle\Event\AuthenticationStarted;
use Rkwadriga\JwtBundle\Exception\TokenValidatorException;
use Rkwadriga\JwtBundle\Service\Config;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private Config                      $config,
        private EventDispatcherInterface    $eventsDispatcher,
        private UserProviderInterface       $userProvider,
        private TokenIdentifierInterface    $identifier,
        private TokenGeneratorInterface     $generator,
        private TokenValidatorInterface     $validator
    ) {}

    public function supports(Request $request): ?bool
    {
        $loginUrl = $this->config->get(ConfigurationParam::LOGIN_URL);
        $refreshUrl = $this->config->get(ConfigurationParam::REFRESH_URL);
        return !in_array($request->get('_route'), [$loginUrl, $refreshUrl]);
    }

    public function authenticate(Request $request): Passport
    {
        // This event can be used to change the authentication process
        $event = new AuthenticationStarted(AuthenticationType::JWT, $request);
        $this->eventsDispatcher->dispatch($event, $event::getName());
        if ($event->getPassport() !== null) {
            return $event->getPassport();
        }

        try {
            // Get token from request
            $tokenString = $this->identifier->identify($request, TokenType::ACCESS);
            // Parse token
            $accessToken = $this->generator->fromString($tokenString, TokenType::ACCESS);
            // Validate token
            $this->validator->validate($accessToken, TokenType::ACCESS);
        } catch (Exception $e) {
            throw new AuthenticationException($e->getMessage(), $e->getCode(), $e);
        }

        // Get user by identifier
        $userIdentifier = $this->config->get(ConfigurationParam::USER_IDENTIFIER);
        $userIdentifierValue = $accessToken->getPayload()[$userIdentifier];
        $userBridge = new UserBadge($userIdentifier, function () use ($userIdentifierValue): ?UserInterface {
            return $this->userProvider->loadUserByIdentifier($userIdentifierValue);
        });

        return new SelfValidatingPassport($userBridge);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // This event can be used to change response
        $event = new AuthenticationFinishedSuccessful(AuthenticationType::JWT);
        $this->eventsDispatcher->dispatch($event, $event::getName());

        return $event->getResponse();
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
        ];

        $code = $exception->getCode() === TokenValidatorException::ACCESS_TOKEN_EXPIRED ? Response::HTTP_UNAUTHORIZED : Response::HTTP_FORBIDDEN;
        $response = new JsonResponse($data, $code);

        // This event can be used to change the error response
        $event = new AuthenticationFinishedUnsuccessful(AuthenticationType::JWT, $request, $exception, $response);
        $this->eventsDispatcher->dispatch($event, $event::getName());

        return $event->getResponse();
    }
}