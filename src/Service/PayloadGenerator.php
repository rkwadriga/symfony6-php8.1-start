<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace App\Service;

use Rkwadriga\JwtBundle\DependencyInjection\PayloadGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PayloadGenerator implements PayloadGeneratorInterface
{
    public function generate(TokenInterface $token, Request $request): array
    {
        return [
            $token->getUserIdentifier() => 'current_user@mail.com',
            'param_2' => 2222,
            'param_3' => 33333
        ];
    }
}