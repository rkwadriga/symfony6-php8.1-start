<?php
/**
 * Created 2021-12-06
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Security\Authenticators;

use Symfony\Component\Security\Core\User\UserInterface;

trait AuthenticationTokenPayloadTrait
{
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