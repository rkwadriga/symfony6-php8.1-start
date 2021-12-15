<?php declare(strict_types=1);
/**
 * Created 2021-12-15
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service;

use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Config
{
    public function __construct(
        private ContainerInterface $container
    ) {}

    public function get(ConfigurationParam $key, mixed $defaultValue = null): mixed
    {
        return $this->container->hasParameter($key->value()) ? $this->container->getParameter($key->value()) : $defaultValue;
    }
}