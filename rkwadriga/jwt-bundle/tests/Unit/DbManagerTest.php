<?php

namespace Rkwadriga\JwtBundle\Tests\Unit;


/**
 * @Run: test rkwadriga/jwt-bundle/tests/Unit/DbManagerTest.php
 */
class DbManagerTest extends AbstractUnitTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        //$routerService = static::getContainer()->get('router');
        //$myCustomService = static::getContainer()->get(CustomService::class);
    }
}
