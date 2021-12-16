<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service;

use Rkwadriga\JwtBundle\DependencyInjection\PayloadGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PayloadGenerator implements PayloadGeneratorInterface
{
    public function generate(TokenInterface $token, Request $request): array
    {
        $payload = ['created' => time()];

        [$user, $identifier] = [$token->getUser(), $token->getUserIdentifier()];
        $getter = 'get' . ucfirst($identifier);
        if (method_exists($user, $getter)) {
            $payload[$identifier] = $user->$getter();
        }

        return $payload;
    }
}