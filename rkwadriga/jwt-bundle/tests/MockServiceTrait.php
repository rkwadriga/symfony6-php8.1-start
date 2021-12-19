<?php
/**
 * Created 2021-12-19
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use Rkwadriga\JwtBundle\Service\Config;

trait MockServiceTrait
{
    protected function mockConfigService(array $methodsMock = []): Config
    {
        return $this->getMock(Config::class, $methodsMock);
    }

    protected function getMock(string $class, array $methodsMock = []): MockObject
    {
        $mock = $this->getMockBuilder($class)->disableOriginalConstructor()->getMock();
        foreach ($methodsMock as $method => $returnValue) {
            $mock->method($method)->willReturn($returnValue);
        }

        return $mock;
    }
}