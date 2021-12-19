<?php declare(strict_types=1);
/**
 * Created 2021-12-19
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Rkwadriga\JwtBundle\Tests\AbstractTestCase;
use Rkwadriga\JwtBundle\Tests\InstanceServiceTrait;
use Rkwadriga\JwtBundle\Tests\MockServiceTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractUnitTestCase extends AbstractTestCase
{
    use MockServiceTrait;
    use InstanceServiceTrait;

    private ContainerInterface $container;

    public function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $this->container = $kernel->getContainer();
    }
}