<?php declare(strict_types=1);
/**
 * Created 2021-12-03
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Security\Authenticators;

use Rkwadriga\JwtBundle\DependencyInjection\Security\AuthenticationType;
use Rkwadriga\JwtBundle\Event\AuthenticationFinishedSuccessfulEvent;
use Rkwadriga\JwtBundle\Event\AuthenticationFinishedUnsuccessfulEvent;
use Rkwadriga\JwtBundle\EventSubscriber\AuthenticationEventSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
use Rkwadriga\JwtBundle\DependencyInjection\Services\TokenGenerator;
use Rkwadriga\JwtBundle\Event\AuthenticationStartedEvent;

class LoginAuthenticator extends AbstractAuthenticator
{
    public const AUTHENTICATION_TYPE = AuthenticationType::LOGIN;

    use AuthenticationTokenPayloadTrait;

    public function __construct(
        private EventDispatcherInterface $eventsDispatcher,
        private UserProviderInterface $userProvider,
        private PasswordHasherFactoryInterface $encoder,
        private SerializerInterface $serializer,
        private TokenGenerator $generator,
        private string $loginUrl,
        private string $loginParam,
        private string $passwordParam
    ) {
        $this->eventsDispatcher->addSubscriber(new AuthenticationEventSubscriber());
    }

    public function supports(Request $request): ?bool
    {
        return $request->get('_route') === $this->loginUrl;
    }

    public function authenticate(Request $request): Passport
    {
        // This event can be used to change authentication process
        $event = new AuthenticationStartedEvent(self::AUTHENTICATION_TYPE, $request);
        $this->eventsDispatcher->dispatch($event, $event::getName());
        if ($event->getPassport() !== null) {
            return $event->getPassport();
        }

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

    public function onAuthenticationSuccess(Request $request, TokenInterface $userToken, string $firewallName): ?Response
    {
        $payload = $this->getPayload($userToken->getUser());
        $token = $this->generator->generate($payload);

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
        $data = [
            'code' => $exception->getCode(),
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];

        $response = new JsonResponse($data, Response::HTTP_FORBIDDEN);

        // This event can be used to change response
        $event = new AuthenticationFinishedUnsuccessfulEvent(self::AUTHENTICATION_TYPE, $request, $exception, $response);
        $this->eventsDispatcher->dispatch($event, $event::getName());

        return $event->getResponse();
    }
}