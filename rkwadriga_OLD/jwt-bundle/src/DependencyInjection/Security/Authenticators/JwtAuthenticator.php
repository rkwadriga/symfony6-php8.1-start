<?php declare(strict_types=1);
/**
 * Created 2021-12-03
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Security\Authenticators;

use Exception;
use Rkwadriga\JwtBundle\DependencyInjection\Security\AuthenticationType;
use Rkwadriga\JwtBundle\Entity\Token;
use Rkwadriga\JwtBundle\Event\AuthenticationFinishedSuccessfulEvent;
use Rkwadriga\JwtBundle\Event\AuthenticationFinishedUnsuccessfulEvent;
use Rkwadriga\JwtBundle\Event\AuthenticationStartedEvent;
use Rkwadriga\JwtBundle\EventSubscriber\AuthenticationEventSubscriber;
use Rkwadriga\JwtBundle\Exception\BaseTokenException;
use Rkwadriga\JwtBundle\Exception\TokenIdentifierException;
use Rkwadriga\JwtBundle\Exception\TokenValidatorException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface as UserTokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Rkwadriga\JwtBundle\DependencyInjection\Services\TokenIdentifier;
use Rkwadriga\JwtBundle\DependencyInjection\Services\TokenValidator;
use Rkwadriga\JwtBundle\Entity\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator
{
    public const AUTHENTICATION_TYPE = AuthenticationType::JWT;

    private TokenInterface $token;

    public function __construct(
        private EventDispatcherInterface $eventsDispatcher,
        private UserProviderInterface $userProvider,
        private TokenIdentifier $identifier,
        private TokenValidator $validator,
        private string $loginUrl,
        private string $refreshUrl,
        private string $loginParam,
    ) {
        $this->eventsDispatcher->addSubscriber(new AuthenticationEventSubscriber());
    }

    public function supports(Request $request): ?bool
    {
        return !in_array($request->get('_route'), [$this->loginUrl, $this->refreshUrl]);
    }

    public function authenticate(Request $request): Passport
    {
        // This event can be used to change authentication process
        $event = new AuthenticationStartedEvent(self::AUTHENTICATION_TYPE, $request);
        $this->eventsDispatcher->dispatch($event, $event::getName());
        if ($event->getPassport() !== null) {
            return $event->getPassport();
        }

        try {
            [$accessTokenData, $refreshTokenData] = $this->identifier->identify($request);
        } catch (\Exception $e) {
            if (!($e instanceof BaseTokenException)) {
                throw $e;
            }
            throw new AuthenticationException($e->getMessage(), Response::HTTP_FORBIDDEN, $e);
        }
        // Refresh token allowed only in "refresh" request
        if ($refreshTokenData !== null) {
            throw new AuthenticationException('Refresh token is not allowed in this request');
        }

        // Validate token
        $this->validator->validateExpiredAt($accessTokenData, AuthenticationException::class);
        $this->validator->validatePayload($accessTokenData, [$this->loginParam], AuthenticationException::class);

        // Lod user by identifier from token payload
        $userIdentifierValue = $accessTokenData->getPayload()[$this->loginParam];
        $userBridge = new UserBadge($this->loginParam, function () use ($userIdentifierValue): ?UserInterface {
            return $this->userProvider->loadUserByIdentifier($userIdentifierValue);
        });

        if ($userBridge->getUser() === null) {
            throw new AuthenticationException('Invalid access token');
        }

        // Remember the token for "authentication_finished_successful" event
        $this->token = new Token(
            $accessTokenData->getCreatedAt(),
            $accessTokenData->getExpiredAt(),
            $accessTokenData->getToken(),
            null,
            $userBridge->getUser()
        );

        return new SelfValidatingPassport($userBridge);
    }

    public function onAuthenticationSuccess(Request $request, UserTokenInterface $token, string $firewallName): ?Response
    {
        // This event can be used to change response
        $event = new AuthenticationFinishedSuccessfulEvent(self::AUTHENTICATION_TYPE, $request, $token, $this->token);
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

        $resultCode = $exception->getCode() === TokenValidatorException::ACCESS_TOKEN_EXPIRED ? Response::HTTP_UNAUTHORIZED : Response::HTTP_FORBIDDEN;
        $response =  new JsonResponse($data, $resultCode);

        // This event can be used to change response
        $event = new AuthenticationFinishedUnsuccessfulEvent(self::AUTHENTICATION_TYPE, $request, $exception, $response);
        $this->eventsDispatcher->dispatch($event, $event::getName());

        return $event->getResponse();
    }
}