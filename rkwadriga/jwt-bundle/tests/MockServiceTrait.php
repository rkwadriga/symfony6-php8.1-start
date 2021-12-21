<?php
/**
 * Created 2021-12-19
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use PHPUnit\Framework\MockObject\MockObject;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Service\Config;
use Rkwadriga\JwtBundle\Service\HeadGenerator;
use Rkwadriga\JwtBundle\Service\PayloadGenerator;
use Rkwadriga\JwtBundle\Service\Serializer;
use Rkwadriga\JwtBundle\Service\TokenGenerator;
use Rkwadriga\JwtBundle\Service\TokenIdentifier;
use Rkwadriga\JwtBundle\Service\TokenResponseCreator;
use Rkwadriga\JwtBundle\Service\TokenValidator;

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

    protected function mockHeadGeneratorService(array $methodsMock = []): HeadGenerator
    {
        return $this->createMock(HeadGenerator::class, $methodsMock);
    }

    protected function mockPayloadGeneratorService(array $methodsMock = []): PayloadGenerator
    {
        return $this->createMock(PayloadGenerator::class, $methodsMock);
    }

    protected function mockSerializerService(array $methodsMock = []): Serializer
    {
        return $this->createMock(Serializer::class, $methodsMock);
    }

    protected function mockTokenGeneratorService(array $methodsMock = []): TokenGenerator
    {
        return $this->createMock(TokenGenerator::class, $methodsMock);
    }

    protected function mockTokenIdentifierService(array $methodsMock = []): TokenIdentifier
    {
        return $this->createMock(TokenIdentifier::class, $methodsMock);
    }

    protected function mockTokenResponseCreatorService(array $methodsMock = []): TokenResponseCreator
    {
        return $this->createMock(TokenResponseCreator::class, $methodsMock);
    }

    protected function mockTokenValidatorService(array $methodsMock = []): TokenValidator
    {
        return $this->createMock(TokenValidator::class, $methodsMock);
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