<?php declare(strict_types=1);
/**
 * Created 2021-12-22
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\E2e;

use Doctrine\ORM\EntityManagerInterface;
use Rkwadriga\JwtBundle\Tests\ConfigDefaultsTrait;
use Rkwadriga\JwtBundle\Service\Router\Generator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractE2eTestCase extends WebTestCase
{
    use ConfigDefaultsTrait;

    protected ?KernelBrowser $client = null;
    protected ContainerInterface $container;
    protected EntityManagerInterface $entityManager;
    protected Generator $router;

    protected function setUp(): void
    {
        parent::setUp();

        // Init client
        $this->getClient();
        $this->container = $this->client->getContainer();
        $this->entityManager = $this->container->get('doctrine.orm.entity_manager');
        $this->router = new Generator($this->container->get('router.default'));
    }

    protected function getClient(): KernelBrowser
    {
        if ($this->client === null) {
            $this->client = self::createClient();
        }

        return $this->client;
    }
}