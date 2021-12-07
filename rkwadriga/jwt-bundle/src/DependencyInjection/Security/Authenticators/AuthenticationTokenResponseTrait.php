<?php
/**
 * Created 2021-12-06
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Security\Authenticators;


use Rkwadriga\JwtBundle\Event\AuthenticationFinishedSuccessfulEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

trait AuthenticationTokenResponseTrait
{
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $this->eventsDispatcher->dispatch(new AuthenticationFinishedSuccessfulEvent($request, $token), AuthenticationFinishedSuccessfulEvent::NAME);

        $payload = $this->getPayload($token->getUser());
        $token = $this->generator->generate($payload);

        $json = $this->serializer->serialize($token, 'json', [
            'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
        ]);
        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    private function getPayload(UserInterface $user): array
    {
        $payload = ['timestamp' => time()];

        $identifier = $user->getUserIdentifier();
        $getter = 'get' . ucfirst($identifier);
        if (method_exists($user, $getter)) {
            $payload[$identifier] = $user->$getter();
        }

        return $payload;
    }
}