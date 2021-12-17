<?php declare(strict_types=1);
/**
 * Created 2021-12-17
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Authenticator;

use Exception;
use Rkwadriga\JwtBundle\DependencyInjection\PayloadGeneratorInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenGeneratorInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenIdentifierInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenResponseCreatorInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\DependencyInjection\TokenValidatorInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenInterface as JwtTokenInterface;
use Rkwadriga\JwtBundle\Enum\AuthenticationType;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenCreationContext;
use Rkwadriga\JwtBundle\Enum\TokenValidationCase;
use Rkwadriga\JwtBundle\Event\AuthenticationFinishedSuccessful;
use Rkwadriga\JwtBundle\Event\AuthenticationFinishedUnsuccessful;
use Rkwadriga\JwtBundle\Event\AuthenticationStarted;
use Rkwadriga\JwtBundle\Event\TokenResponseCreated;
use Rkwadriga\JwtBundle\Exception\TokenValidatorException;
use Rkwadriga\JwtBundle\Service\Config;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Serializer\SerializerInterface;

class RefreshAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private Config                          $config,
        private EventDispatcherInterface        $eventsDispatcher,
        private UserProviderInterface           $userProvider,
        private TokenIdentifierInterface        $identifier,
        private PayloadGeneratorInterface       $payloadGenerator,
        private TokenGeneratorInterface         $generator,
        private TokenValidatorInterface         $validator,
        private SerializerInterface             $serializer,
        private TokenResponseCreatorInterface   $responseCreator,
        private JwtTokenInterface               $oldRefreshToken
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->get('_route') === $this->config->get(ConfigurationParam::REFRESH_URL);
    }

    public function authenticate(Request $request): Passport
    {
        // This event can be used to change the authentication process
        $event = new AuthenticationStarted(AuthenticationType::REFRESH, $request);
        $this->eventsDispatcher->dispatch($event, $event::getName());
        if ($event->getPassport() !== null) {
            return $event->getPassport();
        }

        try {
            // Get tokens from request
            $accessTokenString = $this->identifier->identify($request, TokenType::ACCESS);
            $refreshTokenString = $this->identifier->identify($request, TokenType::REFRESH);

            // Parse tokens
            $accessToken = $this->generator->fromString($accessTokenString, TokenType::ACCESS);
            $refreshToken = $this->generator->fromString($refreshTokenString, TokenType::REFRESH);

            // Validate tokens
            $this->validator->validate($accessToken, TokenType::ACCESS, [], [TokenValidationCase::EXPIRED]);
            $this->validator->validate($refreshToken, TokenType::REFRESH);
            $this->validator->validateRefresh($refreshToken, $accessToken);

            $this->oldRefreshToken = $refreshToken;
        } catch (Exception $e) {
            throw new AuthenticationException($e->getMessage(), $e->getCode(), $e);
        }

        // Get user by identifier
        $userIdentifier = $this->config->get(ConfigurationParam::USER_IDENTIFIER);
        $userIdentifierValue = $accessToken->getPayload()[$userIdentifier];
        $userBridge = new UserBadge($userIdentifier, function () use ($userIdentifierValue): ?UserInterface {
            return $this->userProvider->loadUserByIdentifier($userIdentifierValue);
        });

        if ($userBridge->getUser() === null) {
            throw new AuthenticationException('Invalid access token');
        }

        return new SelfValidatingPassport($userBridge);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Generate payload and create "access" and "refresh" tokens pair
        $payload = $this->payloadGenerator->generate($token, $request);
        $accessToken = $this->generator->fromPayload($payload, TokenType::ACCESS, TokenCreationContext::REFRESH);
        $refreshToken = $this->generator->fromPayload($payload, TokenType::REFRESH, TokenCreationContext::REFRESH);

        $tokenResponse = $this->responseCreator->create($accessToken, $refreshToken);
        // This event can be used to change the token response
        $event = new TokenResponseCreated($tokenResponse);
        $this->eventsDispatcher->dispatch($event, $event::getName());

        $json = $this->serializer->serialize($event->getTokenResponse(), 'json', [
            'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
        ]);
        $response = new JsonResponse($json, Response::HTTP_CREATED, [], true);

        // This event can be used to change response
        $event = new AuthenticationFinishedSuccessful(AuthenticationType::REFRESH, $response);
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
        $event = new AuthenticationFinishedUnsuccessful(AuthenticationType::REFRESH, $request, $exception, $response);
        $this->eventsDispatcher->dispatch($event, $event::getName());

        return $event->getResponse();
    }
}