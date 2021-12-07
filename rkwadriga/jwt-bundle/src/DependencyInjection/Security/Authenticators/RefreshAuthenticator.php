<?php declare(strict_types=1);
/**
 * Created 2021-12-06
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Security\Authenticators;

use Rkwadriga\JwtBundle\DependencyInjection\Services\TokenGenerator;
use Rkwadriga\JwtBundle\DependencyInjection\Services\Encoder;
use Rkwadriga\JwtBundle\DependencyInjection\Services\TokenIdentifier;
use Rkwadriga\JwtBundle\DependencyInjection\Services\TokenValidator;
use Rkwadriga\JwtBundle\Event\AuthenticationFinishedUnsuccessfulEvent;
use Rkwadriga\JwtBundle\EventSubscriber\AuthenticationEventSubscriber;
use Rkwadriga\JwtBundle\Exceptions\TokenIdentifierException;
use Rkwadriga\JwtBundle\Exceptions\TokenValidatorException;
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
    use AuthenticationTokenResponseTrait;

    public function __construct(
        private EventDispatcherInterface $eventsDispatcher,
        private UserProviderInterface $userProvider,
        private TokenIdentifier $identifier,
        private Encoder $encoder,
        private TokenValidator $validator,
        private SerializerInterface $serializer,
        private TokenGenerator $generator,
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
        // Refresh token is required for this request
        [$accessTokenData, $refreshTokenData] = $this->identifier->identify($request, AuthenticationException::class, true);

        // Validate only refresh token expired at - it doesn't matter is the access token expired or not.
        //  And payload of both tokens will be validated in "validateRefreshAndAccessTokensPayload" method
        $this->validator->validateExpiredAt($refreshTokenData, AuthenticationException::class);

        // Check is this refresh token can validate this access token
        $this->validator->validateRefreshAndAccessTokensPayload($accessTokenData, $refreshTokenData, [$this->loginParam], AuthenticationException::class);

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

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->eventsDispatcher->dispatch(new AuthenticationFinishedUnsuccessfulEvent($request, $exception), AuthenticationFinishedUnsuccessfulEvent::NAME);

        $previous = $exception->getPrevious();
        $message = $previous instanceof TokenValidatorException || $previous instanceof TokenIdentifierException
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

        return new JsonResponse($data, $resultCode);
    }
}