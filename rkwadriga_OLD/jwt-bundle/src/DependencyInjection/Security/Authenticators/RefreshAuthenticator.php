<?php declare(strict_types=1);
/**
 * Created 2021-12-06
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Security\Authenticators;

use Rkwadriga\JwtBundle\DependencyInjection\Security\AuthenticationType;
use Rkwadriga\JwtBundle\DependencyInjection\Services\DbService;
use Rkwadriga\JwtBundle\DependencyInjection\Services\TokenGenerator;
use Rkwadriga\JwtBundle\DependencyInjection\Services\Encoder;
use Rkwadriga\JwtBundle\DependencyInjection\Services\TokenIdentifier;
use Rkwadriga\JwtBundle\DependencyInjection\Services\TokenRefresher;
use Rkwadriga\JwtBundle\DependencyInjection\Services\TokenValidator;
use Rkwadriga\JwtBundle\Entity\TokenData;
use Rkwadriga\JwtBundle\Event\AuthenticationFinishedSuccessfulEvent;
use Rkwadriga\JwtBundle\Event\AuthenticationFinishedUnsuccessfulEvent;
use Rkwadriga\JwtBundle\Event\AuthenticationStarted;
use Rkwadriga\JwtBundle\EventSubscriber\AuthenticationEventSubscriber;
use Rkwadriga\JwtBundle\Exception\BaseTokenException;
use Rkwadriga\JwtBundle\Exception\TokenIdentifierException;
use Rkwadriga\JwtBundle\Exception\TokenRefresherException;
use Rkwadriga\JwtBundle\Exception\TokenValidatorException;
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
use Symfony\Component\Serializer\SerializerInterface;

class RefreshAuthenticator extends AbstractAuthenticator
{
    public const AUTHENTICATION_TYPE = AuthenticationType::REFRESH;

    use AuthenticationTokenPayloadTrait;

    private TokenData $currentRefreshToken;

    public function __construct(
        private EventDispatcherInterface $eventsDispatcher,
        private UserProviderInterface $userProvider,
        private TokenIdentifier $identifier,
        private Encoder $encoder,
        private TokenValidator $validator,
        private SerializerInterface $serializer,
        private TokenRefresher $refresher,
        private string $refreshUrl,
        private string $loginParam
    ) {
        $this->eventsDispatcher->addSubscriber(new AuthenticationEventSubscriber());
    }

    public function supports(Request $request): ?bool
    {
        return $request->get('_route') === $this->refreshUrl;
    }

    public function authenticate(Request $request): Passport
    {
        // This event can be used to change authentication process
        $event = new AuthenticationStarted(self::AUTHENTICATION_TYPE, $request);
        $this->eventsDispatcher->dispatch($event, $event::getName());
        if ($event->getPassport() !== null) {
            return $event->getPassport();
        }

        // Refresh token is required for this request
        try {
            [$accessTokenData, $refreshTokenData] = $this->identifier->identify($request, AuthenticationException::class, true);
            // Validate only refresh token expired at - it doesn't matter is the access token expired or not.
            //  And payload of both tokens will be validated in "validateRefreshAndAccessTokensPayload" method
            $this->validator->validateExpiredAt($refreshTokenData, AuthenticationException::class);

            // Check is this refresh token can validate this access token
            $this->validator->validateRefreshAndAccessTokensPayload($accessTokenData, $refreshTokenData, [$this->loginParam], AuthenticationException::class);
        } catch (\Exception $e) {
            if (!($e instanceof BaseTokenException)) {
                throw $e;
            }
            throw new AuthenticationException($e->getMessage(), Response::HTTP_FORBIDDEN, $e);
        }
        $this->currentRefreshToken = $refreshTokenData;

        // Load user by identifier from token payload
        $userIdentifierValue = $accessTokenData->getPayload()[$this->loginParam];
        $userBridge = new UserBadge($this->loginParam, function () use ($userIdentifierValue): ?UserInterface {
            return $this->userProvider->loadUserByIdentifier($userIdentifierValue);
        });

        if ($userBridge->getUser() === null) {
            throw new AuthenticationException('Invalid access token');
        }

        return new SelfValidatingPassport($userBridge);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $userToken, string $firewallName): ?Response
    {
        $payload = $this->getPayload($userToken->getUser());
        try {
            $token = $this->refresher->refreshToken($payload, $this->currentRefreshToken);
        } catch (\Exception $e) {
            if (!($e instanceof BaseTokenException)) {
                throw $e;
            }
            $exception = new AuthenticationException($e->getMessage(), Response::HTTP_FORBIDDEN, $e);
            return $this->onAuthenticationFailure($request, $exception);
        }

        $json = $this->serializer->serialize($token, 'json', [
            'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
        ]);
        $response = new JsonResponse($json, Response::HTTP_CREATED, [], true);

        // This event can be used to change response
        $event = new AuthenticationFinishedSuccessfulEvent(self::AUTHENTICATION_TYPE, $request, $userToken, $token, $response);
        $this->eventsDispatcher->dispatch($event, $event::getName());

        return $event->getResponse();
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $previous = $exception->getPrevious();
        $message = $previous instanceof BaseTokenException
            ? $exception->getMessage()
            : strtr($exception->getMessageKey(), $exception->getMessageData());

        $data = [
            'code' => $exception->getCode(),
            'message' => $message,
        ];

        if (in_array($data['code'], [
                TokenValidatorException::ACCESS_TOKEN_EXPIRED,
                TokenValidatorException::REFRESH_TOKEN_EXPIRED
            ])
        ) {
            $resultCode = Response::HTTP_UNAUTHORIZED;
        } else {
            $resultCode = Response::HTTP_FORBIDDEN;
        }

        $response = new JsonResponse($data, $resultCode);

        // This event can be used to change response
        $event = new AuthenticationFinishedUnsuccessfulEvent(self::AUTHENTICATION_TYPE, $request, $exception, $response);
        $this->eventsDispatcher->dispatch($event, $event::getName());

        return $event->getResponse();
    }
}