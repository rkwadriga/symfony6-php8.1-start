<?php declare(strict_types=1);
/**
 * Created 2021-12-22
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\E2e;

use Doctrine\ORM\EntityManagerInterface;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Tests\AuthenticationTrait;
use Rkwadriga\JwtBundle\Tests\ConfigDefaultsTrait;
use Rkwadriga\JwtBundle\Service\Router\Generator;
use Rkwadriga\JwtBundle\Tests\CreateUserTableTrait;
use Rkwadriga\JwtBundle\Tests\RequestParamsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractE2eTestCase extends WebTestCase
{
    use ConfigDefaultsTrait;
    use RequestParamsTrait;
    use AuthenticationTrait;
    use CreateUserTableTrait;

    protected ?KernelBrowser $client = null;
    protected ContainerInterface $container;
    protected EntityManagerInterface $entityManager;
    protected Generator $router;
    protected string $loginUrl;
    protected string $refreshUrl;
    protected string $loginParam;
    protected string $passwordParam;

    protected function setUp(): void
    {
        parent::setUp();

        // Init client
        $this->getClient();
        $this->container = $this->client->getContainer();
        $this->entityManager = $this->container->get('doctrine.orm.entity_manager');
        $this->router = new Generator($this->container->get('router.default'));
        $this->loginUrl = $this->getConfigDefault(ConfigurationParam::LOGIN_URL);
        $this->refreshUrl = $this->getConfigDefault(ConfigurationParam::REFRESH_URL);
        $this->loginParam = $this->getConfigDefault(ConfigurationParam::LOGIN_PARAM);
        $this->passwordParam = $this->getConfigDefault(ConfigurationParam::PASSWORD_PARAM);
    }

    protected function send(string|array $route, array $params = [], array $headers = []): Crawler
    {
        $client = $this->getClient();
        [$method, $uri] = $this->router->createRoute($route);

        $client->setServerParameter('CONTENT_TYPE', $this->requestContentType);
        $client->setServerParameter('HTTP_ACCEPT', $this->requestAssept);
        if ($this->getToken() !== null) {
            $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $this->getToken());
        }

        return $client->request($method, $uri, [], [], $headers, json_encode($params));
    }

    protected function getClient(): KernelBrowser
    {
        if ($this->client === null) {
            $this->client = self::createClient();
        }

        return $this->client;
    }
}