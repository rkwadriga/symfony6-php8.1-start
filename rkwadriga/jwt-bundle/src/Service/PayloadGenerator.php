<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service;

use Rkwadriga\JwtBundle\DependencyInjection\PayloadGeneratorInterface;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class PayloadGenerator implements PayloadGeneratorInterface
{
    public function __construct(
        private Config $config,
    ) {}

    public function generate(TokenInterface $token, Request $request): array
    {
        $payload = ['created' => time()];

        [$user, $identifier] = [$token->getUser(), $this->config->get(ConfigurationParam::USER_IDENTIFIER)];
        $getter = 'get' . ucfirst($identifier);
        if (method_exists($user, $getter)) {
            $payload[$identifier] = $user->$getter();
        }

        return $payload;
    }
}