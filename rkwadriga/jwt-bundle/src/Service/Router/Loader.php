<?php declare(strict_types=1);
/**
 * Created 2021-12-23
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service\Router;

use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Service\Config;
use Symfony\Component\Config\Loader\Loader as BaseLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Loader extends BaseLoader
{
    public function __construct(
        private Config $config,
        private $loaded = false
    ) {}

    public function load(mixed $resource, string $type = null): ?RouteCollection
    {
        if ($this->loaded) {
            return null;
        }

        $newRoutes = [
            $this->config->get(ConfigurationParam::LOGIN_URL) => [
                'path' => '/api/token',
                'method' => 'POST',
            ],
            $this->config->get(ConfigurationParam::REFRESH_URL) => [
                'path' => '/api/token',
                'method' => 'PUT',
            ],
            'rkwadriga_jwt_test_route' => [
                'path' => '/api/rkwadriga-jwt-test-route',
                'method' => 'GET',
            ],
        ];

        $routes = new RouteCollection();
        foreach ($newRoutes as $name => $params) {
            $routes->add($name, new Route($params['path'], [], [], [], null, [], $params['method']));
        }

        $this->loaded = true;

        return $routes;
    }

    public function supports(mixed $resource, string $type = null): bool
    {
        return $type === 'annotation';
    }

}