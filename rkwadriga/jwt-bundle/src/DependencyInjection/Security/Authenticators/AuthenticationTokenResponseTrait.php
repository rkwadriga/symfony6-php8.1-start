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
    public function onAuthenticationSuccess(Request $request, TokenInterface $userToken, string $firewallName): ?Response
    {
        $payload = $this->getPayload($userToken->getUser());
        $token = $this->generator->generate($payload);

        $json = $this->serializer->serialize($token, 'json', [
            'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
        ]);
        $response = new JsonResponse($json, Response::HTTP_CREATED, [], true);

        // This event can be used to change response
        $event = new AuthenticationFinishedSuccessfulEvent($request, $userToken, $token, $response);
        $this->eventsDispatcher->dispatch($event, $event::getName());

        return $event->getResponse();
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