<?php declare(strict_types=1);
/**
 * Created 2021-12-22
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\E2e;

use Doctrine\ORM\EntityManagerInterface;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenParamLocation;
use Rkwadriga\JwtBundle\Enum\TokenParamType;
use Rkwadriga\JwtBundle\Tests\AuthenticationTrait;
use Rkwadriga\JwtBundle\Tests\ConfigDefaultsTrait;
use Rkwadriga\JwtBundle\Service\Router\Generator;
use Rkwadriga\JwtBundle\Tests\CustomAssertionsTrait;
use Rkwadriga\JwtBundle\Tests\DatabaseTrait;
use Rkwadriga\JwtBundle\Tests\DefaultParamsTrait;
use Rkwadriga\JwtBundle\Tests\RequestParamsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

abstract class AbstractE2eTestCase extends WebTestCase
{
    use DefaultParamsTrait;
    use ConfigDefaultsTrait;
    use DatabaseTrait;
    use RequestParamsTrait;
    use AuthenticationTrait;
    use CustomAssertionsTrait;

    protected ?KernelBrowser $client = null;
    protected ContainerInterface $container;
    protected EntityManagerInterface $entityManager;
    protected Generator $router;
    protected PasswordHasherFactoryInterface $passwordEncoderFactory;
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
        $this->passwordEncoderFactory = $this->container->get('security.password_hasher_factory');
        $this->loginUrl = $this->getConfigDefault(ConfigurationParam::LOGIN_URL);
        $this->refreshUrl = $this->getConfigDefault(ConfigurationParam::REFRESH_URL);
        $this->loginParam = $this->getConfigDefault(ConfigurationParam::LOGIN_PARAM);
        $this->passwordParam = $this->getConfigDefault(ConfigurationParam::PASSWORD_PARAM);
    }

    protected function send(string|array $route, array $params = [], array $headers = []): Crawler
    {
        [$method, $uri] = $this->router->createRoute($route);

        return $this->sendRequest($method, $uri, [], $params, $headers);
    }

    protected function sendRequest(string $method, string $uri, array $getParameters = [], array $postParameters = [], array $headers = []): Crawler
    {
        // Clear client cache
        $client = $this->getClient();
        $client->setServerParameter('CONTENT_TYPE', $this->requestContentType);
        $client->setServerParameter('HTTP_ACCEPT', $this->requestAssept);
        $headerTokenParam = null;
        if ($this->getToken() !== null) {
            $token = $this->getToken();
            [$tokenType, $tokenLocation, $tokenParamName] = [
                $this->getConfigDefault(ConfigurationParam::TOKEN_TYPE),
                $this->getConfigDefault(ConfigurationParam::ACCESS_TOKEN_LOCATION),
                $this->getConfigDefault(ConfigurationParam::ACCESS_TOKEN_PARAM_NAME),
            ];

            switch ($tokenLocation) {
                case TokenParamLocation::HEADER->value:
                    if ($tokenType !== TokenParamType::SIMPLE->value) {
                        $token = $tokenType . ' ' . $token;
                    }
                    $headerName = 'HTTP_' . strtoupper($tokenParamName);
                    $client->setServerParameter($headerName, $token);
                    $headerTokenParam = $headerName;
                    break;
                case TokenParamLocation::URI->value:
                    $getParameters[$tokenParamName] = $token;
                    break;
                default:
                    $postParameters[$tokenParamName] = $token;
                    break;
            }
        }

        $result = $client->request($method, $uri, $getParameters, [], $headers, json_encode($postParameters));
        // Remove token from headers after request
        if ($headerTokenParam !== null) {
            $client->setServerParameters([$headerTokenParam => null]);
        }

        return $result;
    }

    protected function getClient(): KernelBrowser
    {
        if ($this->client === null) {
            $this->client = self::createClient();
        }

        return $this->client;
    }
}