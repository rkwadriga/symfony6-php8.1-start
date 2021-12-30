<?php declare(strict_types=1);
/**
 * Created 2021-12-15
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Authenticator;

use Rkwadriga\JwtBundle\DependencyInjection\DbManagerInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenGeneratorInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Enum\AuthenticationType;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenCreationContext;
use Rkwadriga\JwtBundle\Enum\TokenRefreshingContext;
use Rkwadriga\JwtBundle\Event\AuthenticationFinishedSuccessful;
use Rkwadriga\JwtBundle\Event\AuthenticationFinishedUnsuccessful;
use Rkwadriga\JwtBundle\Event\AuthenticationStarted;
use Rkwadriga\JwtBundle\Event\TokenResponseCreated;
use Rkwadriga\JwtBundle\Exception\TokenGeneratorException;
use Rkwadriga\JwtBundle\Service\Config;
use Rkwadriga\JwtBundle\DependencyInjection\PayloadGeneratorInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenResponseCreatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Serializer\SerializerInterface;

class LoginAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private Config                         $config,
        private EventDispatcherInterface       $eventsDispatcher,
        private UserProviderInterface          $userProvider,
        private PasswordHasherFactoryInterface $encoderFactory,
        private SerializerInterface            $serializer,
        private PayloadGeneratorInterface      $payloadGenerator,
        private TokenGeneratorInterface        $generator,
        private DbManagerInterface             $dbManager,
        private TokenResponseCreatorInterface  $responseCreator
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->get('_route') === $this->config->get(ConfigurationParam::LOGIN_URL);
    }

    public function authenticate(Request $request): Passport
    {
        // This event can be used to change authentication process
        $event = new AuthenticationStarted(AuthenticationType::LOGIN, $request);
        $this->eventsDispatcher->dispatch($event, $event::getName());
        if ($event->getPassport() !== null) {
            return $event->getPassport();
        }

        // Get login params
        $params = json_decode($request->getContent(), true);
        if (!is_array($params)) {
            throw new CustomUserMessageAuthenticationException('Invalid request');
        }
        [$loginParam, $passwordParam] = [$this->config->get(ConfigurationParam::LOGIN_PARAM), $this->config->get(ConfigurationParam::PASSWORD_PARAM)];
        if (!isset($params[$loginParam]) || !isset($params[$passwordParam])) {
            throw new CustomUserMessageAuthenticationException("Params \"{$loginParam}\" and \"{$passwordParam}\" are required");
        }

        // Check username, password and get user by identifier
        $userIdentifier = $this->config->get(ConfigurationParam::USER_IDENTIFIER);
        $userBridge = new UserBadge($userIdentifier, function () use ($params, $loginParam): ?UserInterface {
            return $this->userProvider->loadUserByIdentifier($params[$loginParam]);
        });
        // Try to find the user to throw an exception if it's not found
        $user = $userBridge->getUser();
        // Check user's password
        if ($user instanceof PasswordAuthenticatedUserInterface
            && !$this->encoderFactory->getPasswordHasher($user)->verify($user->getPassword(), $params[$passwordParam])
        ) {
            throw new BadCredentialsException('Bad credentials');
        }

        return new SelfValidatingPassport($userBridge);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Generate payload and create "access" and "refresh" tokens pair
        $payload = $this->payloadGenerator->generate($token, $request);
        $accessToken = $this->generator->fromPayload($payload, TokenType::ACCESS, TokenCreationContext::LOGIN);
        $refreshToken = $this->generator->fromPayload($payload, TokenType::REFRESH, TokenCreationContext::LOGIN);

        // Write refresh token to DB if needed
        if ($this->config->get(ConfigurationParam::REFRESH_TOKEN_IN_DB)) {
            // Check user ID in payload
            $userIdentifier = $this->config->get(ConfigurationParam::USER_IDENTIFIER);
            if (!isset($payload[$userIdentifier])) {
                throw new TokenGeneratorException("User identifier \"{$userIdentifier}\" missed in token's payload", TokenGeneratorException::INVALID_PAYLOAD);
            }
            $this->dbManager->writeRefreshToken($accessToken->getPayload()[$userIdentifier], $refreshToken, TokenRefreshingContext::LOGIN);
        }

        $tokenResponse = $this->responseCreator->create($accessToken, $refreshToken);
        // This event can be used to change the token response
        $event = new TokenResponseCreated($tokenResponse);
        $this->eventsDispatcher->dispatch($event, $event::getName());

        $json = $this->serializer->serialize($event->getTokenResponse(), 'json', [
            'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
        ]);
        $response = new JsonResponse($json, Response::HTTP_CREATED, [], true);

        // This event can be used to change response
        $event = new AuthenticationFinishedSuccessful(AuthenticationType::LOGIN, $response);
        $this->eventsDispatcher->dispatch($event, $event::getName());

        return $event->getResponse();
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage() ?: strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];

        $response = new JsonResponse($data, Response::HTTP_FORBIDDEN);
        // This event can be used to change the error response
        $event = new AuthenticationFinishedUnsuccessful(AuthenticationType::LOGIN, $request, $exception, $response);
        $this->eventsDispatcher->dispatch($event, $event::getName());

        return $event->getResponse();
    }
}