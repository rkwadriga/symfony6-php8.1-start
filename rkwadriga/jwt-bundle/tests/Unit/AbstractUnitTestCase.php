<?php declare(strict_types=1);
/**
 * Created 2021-12-19
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Rkwadriga\JwtBundle\Tests\AbstractTestCase;
use Rkwadriga\JwtBundle\Tests\ConfigDefaultsTrait;
use Rkwadriga\JwtBundle\Tests\InstanceTokenTrait;
use Rkwadriga\JwtBundle\Tests\InstanceServiceTrait;
use Rkwadriga\JwtBundle\Tests\MockServiceTrait;
use Rkwadriga\JwtBundle\Tests\ReflectionTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractUnitTestCase extends AbstractTestCase
{
    use ConfigDefaultsTrait;
    use MockServiceTrait;
    use InstanceServiceTrait;
    use InstanceTokenTrait;
    use ReflectionTrait;

    protected const USER_ID = 'test_user@gmail.com';
    protected const SECRET_KEY = 'Lm870sdfpOki78Yr6Tsdfkl09Iksdjf71sdfk';

    protected ContainerInterface $container;
    protected EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $this->container = $kernel->getContainer();
        $this->entityManager = $this->container->get('doctrine.orm.entity_manager');
    }
}