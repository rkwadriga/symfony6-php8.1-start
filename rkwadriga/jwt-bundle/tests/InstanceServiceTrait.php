<?php
/**
 * Created 2021-12-19
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Rkwadriga\JwtBundle\Service\Config;

trait InstanceServiceTrait
{
    public function getConfigService(): Config
    {
        return new Config($this->container);
    }
}