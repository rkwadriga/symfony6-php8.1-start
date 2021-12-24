<?php
/**
 * Created 2021-12-19
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Rkwadriga\JwtBundle\Entity\Token;
use Rkwadriga\JwtBundle\Entity\User;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Service\Config;
use Rkwadriga\JwtBundle\Service\DbManager;
use Rkwadriga\JwtBundle\Service\HeadGenerator;
use Rkwadriga\JwtBundle\Service\PayloadGenerator;
use Rkwadriga\JwtBundle\Service\Serializer;
use Rkwadriga\JwtBundle\Service\TokenGenerator;
use Rkwadriga\JwtBundle\Service\TokenIdentifier;
use Rkwadriga\JwtBundle\Service\TokenResponseCreator;
use Rkwadriga\JwtBundle\Service\TokenValidator;
use Rkwadriga\JwtBundle\Tests\Entity\ResponseSerializer;
use Symfony\Bridge\Doctrine\Security\User\EntityUserProvider;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\PlaintextPasswordHasher;

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

    protected function mockDbManagerService(array $methodsMock = []): DbManager
    {
        return $this->createMock(DbManager::class, $methodsMock);
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

    protected function mockPasswordHasherFactory(string $hashingResult = '12345', bool $verifyingResult = true): PasswordHasherFactory
    {
        $hasherMock = $this->createMock(PlaintextPasswordHasher::class, ['hash' => $hashingResult, 'verify' => $verifyingResult]);
        return $this->createMock(PasswordHasherFactory::class, ['getPasswordHasher' => $hasherMock]);
    }

    protected function mockUserProvider(User|Exception $user): EntityUserProvider
    {
        return $this->createMock(EntityUserProvider::class, ['loadUserByIdentifier' => $user]);
    }

    protected function mockResponseSerializer(array $methodsMock = []): ResponseSerializer
    {
        return $this->createMock(ResponseSerializer::class, $methodsMock);
    }

    protected function mockToken(Token $token, array $methodsMock = []): Token
    {
        $defaultMethodsMock = [
            'getType' => $token->getType(),
            'getToken' => $token->getToken(),
            'getCreatedAt' => $token->getCreatedAt(),
            'getExpiredAt' => $token->getExpiredAt(),
            'getHead' => $token->getHead(),
            'getPayload' => $token->getPayload(),
            'getSignature' => $token->getSignature(),
        ];

        return $this->createMock(Token::class, array_merge($defaultMethodsMock, $methodsMock));
    }

    protected function createMock(string $class, array $methodsMock = []): MockObject
    {
        $mock = $this->getMockBuilder($class)->disableOriginalConstructor()->getMock();
        foreach ($methodsMock as $method => $returnValue) {
            if (is_array($returnValue) && isset($returnValue['__map'])) {
                $mock->method($method)->willReturnMap($returnValue['__map']);
            } elseif ($returnValue instanceof Exception) {
                $mock->method($method)->willThrowException($returnValue);
            } else {
                $mock->method($method)->willReturn($returnValue);
            }
        }

        return $mock;
    }
}