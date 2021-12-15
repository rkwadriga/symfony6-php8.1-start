<?php declare(strict_types=1);
/**
 * Created 2021-12-15
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Authenticator;

use Rkwadriga\JwtBundle\Enum\AuthenticationType;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Event\AuthenticationStartedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Rkwadriga\JwtBundle\Service\Config;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Serializer\SerializerInterface;

class LoginAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private Config $config,
        private EventDispatcherInterface $eventsDispatcher,
        private UserProviderInterface $userProvider,
        private PasswordHasherFactoryInterface $encoder,
        private SerializerInterface $serializer,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->get('_route') === $this->config->get(ConfigurationParam::LOGIN_URL);
    }

    public function authenticate(Request $request): Passport
    {
        // This event can be used to change authentication process
        $event = new AuthenticationStartedEvent(AuthenticationType::LOGIN, $request);
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

        // Check username, password and get user
        $userBridge = new UserBadge($loginParam, function () use ($params, $loginParam): ?UserInterface {
            return $this->userProvider->loadUserByIdentifier($params[$loginParam]);
        });
        $user = $userBridge->getUser();
        if ($user === null ||
            !$this->encoder->getPasswordHasher($user)->verify($user->getPassword(), $params[$passwordParam])
        ) {
            throw new CustomUserMessageAuthenticationException("Invalid \"{$loginParam}\" or \"{$passwordParam}\"");
        }

        return new SelfValidatingPassport($userBridge);
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