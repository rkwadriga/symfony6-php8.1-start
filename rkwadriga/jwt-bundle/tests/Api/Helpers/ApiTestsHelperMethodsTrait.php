<?php
/**
 * Created 2021-12-04
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Api\Helpers;


use App\Entity\User;
use PHPUnit\TextUI\RuntimeException;
use Rkwadriga\JwtBundle\Tests\Api\fixtures\UserFixture;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

trait ApiTestsHelperMethodsTrait
{
    private function request(string|array $route, array $params = []): HttpResponse
    {
        if (is_array($route)) {
            [$route, $routeParams] = $route;
        } else {
            $routeParams = [];
        }
        [$method, $uri] = $this->createRoute($route, $routeParams);

        $this->client->request($method, $uri, [], [], [], json_encode($params));
        return $this->getResponse();
    }

    private function getResponse(): HttpResponse
    {
        return new HttpResponse($this->getResponseStatus(), $this->getResponseBody());
    }

    private function getResponseStatus(): int
    {
        return $this->client->getResponse()->getStatusCode();
    }

    private function getResponseBody(?string $paramName = null, mixed $defaultValue = null): ?array
    {
        $body = $this->client->getResponse()->getContent();
        if (!$body) {
            return null;
        }

        $params = json_decode($body, true);
        if ($params === null) {
            throw new RuntimeException('Invalid response format');
        }

        if ($paramName === null) {
            return $params;
        }

        return $params[$paramName] ?? $defaultValue;
    }

    private function createRoute(string $routeName, array $routeParams = []): array
    {
        $route = $this->router->getRouteCollection()->get($routeName);
        if ($route === null) {
            throw new ParameterNotFoundException("Invalid route name \"{$routeName}\"");
        }

        $methods = $route->getMethods();
        $uri = $this->router->generate($routeName, $routeParams);

        return [array_shift($methods), $uri];
    }

    private function createUser(
        string $email = UserFixture::EMAIL,
        string $password = UserFixture::PASSWORD,
        string $firstName = UserFixture::FIRST_NAME,
        string $lastName = UserFixture::LAST_NAME
    ): User {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user !== null) {
            return $user;
        }

        $user = new User();
        $user
            ->setEmail($email)
            ->setPassword($this->encoder->getPasswordHasher($user)->hash($password))
            ->setFirstName($firstName)
            ->setLastName($lastName);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}