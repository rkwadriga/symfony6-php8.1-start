<?php
/**
 * Created 2021-12-19
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Rkwadriga\JwtBundle\Authenticator\JwtAuthenticator;
use Rkwadriga\JwtBundle\Authenticator\LoginAuthenticator;
use Rkwadriga\JwtBundle\Authenticator\RefreshAuthenticator;
use Rkwadriga\JwtBundle\DependencyInjection\HeadGeneratorInterface;
use Rkwadriga\JwtBundle\DependencyInjection\SerializerInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenInterface;
use Rkwadriga\JwtBundle\Service\Config;
use Rkwadriga\JwtBundle\Service\DbManager;
use Rkwadriga\JwtBundle\Service\HeadGenerator;
use Rkwadriga\JwtBundle\Service\PayloadGenerator;
use Rkwadriga\JwtBundle\Service\Serializer;
use Rkwadriga\JwtBundle\Service\TokenGenerator;
use Rkwadriga\JwtBundle\Service\TokenIdentifier;
use Rkwadriga\JwtBundle\Service\TokenResponseCreator;
use Rkwadriga\JwtBundle\Service\TokenValidator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Bridge\Doctrine\Security\User\EntityUserProvider;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\Serializer\SerializerInterface as ResponseSerializerInterface;
use Symfony\Component\Serializer\Serializer as ResponseSerializer;

trait InstanceServiceTrait
{
    protected function createConfigServiceInstance(): Config
    {
        return new Config($this->container);
    }

    protected function createDbManagerInstance(?Config $configService = null): DbManager
    {
        return new DbManager(
            $configService ?? $this->createConfigServiceInstance(),
            $this->container->get('doctrine.orm.entity_manager')
        );
    }

    protected function createHeadGeneratorInstance(?Config $configService = null): HeadGenerator
    {
        return new HeadGenerator($configService ?? $this->createConfigServiceInstance());
    }

    protected function createPayloadGeneratorInstance(?Config $configService = null): PayloadGenerator
    {
        return new PayloadGenerator($configService ?? $this->createConfigServiceInstance());
    }

    protected function createSerializerInstance(?Config $configService = null): Serializer
    {
        return new Serializer($configService ?? $this->createConfigServiceInstance());
    }

    protected function createTokenIdentifierInstance(?Config $configService = null): TokenIdentifier
    {
        return new TokenIdentifier($configService ?? $this->createConfigServiceInstance());
    }

    protected function createTokenResponseCreatorInstance(): TokenResponseCreator
    {
        return new TokenResponseCreator();
    }

    protected function createTokenValidatorInstance(?Config $configService = null): TokenValidator
    {
        return new TokenValidator($configService ?? $this->createConfigServiceInstance());
    }

    protected function createTokenGeneratorInstance(
        ?Config $configService = null,
        ?SerializerInterface $serializer = null,
        ?HeadGeneratorInterface $headGenerator = null,
        ?ResponseSerializerInterface $responseSerializer = null,
        ?EventDispatcherInterface $eventDispatcher = null
    ): TokenGenerator {
        $configService = $configService ?? $this->createConfigServiceInstance();

        return new TokenGenerator(
            $configService,
            $eventDispatcher ?? $this->createMock(EventDispatcher::class),
            $serializer ?? $this->createSerializerInstance($configService),
            $headGenerator ?? $this->createHeadGeneratorInstance($configService)
        );
    }

    protected function createLoginAuthenticatorInstance(
        ?UserProviderInterface $userProvider = null,
        ?PasswordHasherFactoryInterface $hasherFactory = null,
        ?Config $configService = null,
        ?PayloadGenerator $payloadGenerator = null,
        ?TokenGenerator $tokenGenerator = null,
        ?DbManager $dbManager = null,
        ?TokenResponseCreator $tokenResponseCreator = null,
        ?ResponseSerializerInterface $responseSerializer = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): LoginAuthenticator {
        return new LoginAuthenticator(
            $configService ?? $this->createConfigServiceInstance(),
            $eventDispatcher ?? $this->createMock(EventDispatcher::class),
            $userProvider ?? $this->createMock(EntityUserProvider::class),
            $hasherFactory ?? $this->createMock(PasswordHasherFactory::class),
            $responseSerializer ?? $this->createMock(ResponseSerializer::class),
            $payloadGenerator ?? $this->createMock(PayloadGenerator::class),
            $tokenGenerator ?? $this->createMock(TokenGenerator::class),
            $dbManager ?? $this->createMock(DbManager::class),
            $tokenResponseCreator ?? $this->createMock(TokenResponseCreator::class),
        );
    }

    protected function createRefreshAuthenticatorInstance(
        ?UserProviderInterface $userProvider = null,
        ?TokenIdentifier $identifier = null,
        ?TokenGenerator $tokenGenerator = null,
        ?TokenValidator $tokenValidator = null,
        ?Config $configService = null,
        ?DbManager $dbManager = null,
        ?PayloadGenerator $payloadGenerator = null,
        ?TokenResponseCreator $tokenResponseCreator = null,
        ?ResponseSerializerInterface $responseSerializer = null,
        ?TokenInterface $oldRefreshToken = null,
        mixed $userID = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): RefreshAuthenticator {
        return new RefreshAuthenticator(
            $configService ?? $this->createConfigServiceInstance(),
            $eventDispatcher ?? $this->createMock(EventDispatcher::class),
            $userProvider ?? $this->createMock(EntityUserProvider::class),
            $identifier ?? $this->createMock(TokenIdentifier::class),
            $payloadGenerator ?? $this->createMock(PayloadGenerator::class),
            $tokenGenerator ?? $this->createMock(TokenGenerator::class),
            $tokenValidator ?? $this->createMock(TokenValidator::class),
            $responseSerializer ?? $this->createMock(ResponseSerializer::class),
            $dbManager ?? $this->createMock(DbManager::class),
            $tokenResponseCreator ?? $this->createMock(TokenResponseCreator::class),
            $oldRefreshToken,
            $userID,
        );
    }

    protected function createJwtAuthenticatorInstance (
        ?TokenIdentifier $identifier = null,
        ?TokenGenerator $tokenGenerator = null,
        ?TokenValidator $tokenValidator = null,
        ?Config $configService = null,
        ?UserProviderInterface $userProvider = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): JwtAuthenticator {
        return new JwtAuthenticator(
            $configService ?? $this->createConfigServiceInstance(),
            $eventDispatcher ?? $this->createMock(EventDispatcher::class),
            $userProvider ?? $this->createMock(EntityUserProvider::class),
            $identifier ?? $this->createMock(TokenIdentifier::class),
            $tokenGenerator ?? $this->createMock(TokenGenerator::class),
            $tokenValidator ?? $this->createMock(TokenValidator::class)
        );
    }
}