<?php
/**
 * Created 2021-12-19
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Service\Config;

trait MockServiceTrait
{
    protected function mockConfigService(array $returnValues = []): Config
    {
        $returnValuesMap = [];
        foreach (ConfigurationParam::cases() as $param) {
            $returnValuesMap[] = [$param, null, $returnValues[$param->value] ?? $this->getConfigDefault($param)];
        }

        return $this->createMock(Config::class, ['get' => ['__map' => $returnValuesMap]]);
    }

    protected function createMock(string $class, array $methodsMock = []): MockObject
    {
        $mock = $this->getMockBuilder($class)->disableOriginalConstructor()->getMock();
        foreach ($methodsMock as $method => $returnValue) {
            if (is_array($returnValue) && isset($returnValue['__map'])) {
                $mock->method($method)->willReturnMap($returnValue['__map']);
            } else {
                $mock->method($method)->willReturn($returnValue);
            }
        }

        return $mock;
    }
}