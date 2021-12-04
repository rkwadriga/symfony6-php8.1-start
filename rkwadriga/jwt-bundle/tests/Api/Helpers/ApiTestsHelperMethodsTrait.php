<?php
/**
 * Created 2021-12-04
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Api\Helpers;


use App\Entity\User;
use Rkwadriga\JwtBundle\Tests\Api\fixtures\UserFixture;
use Symfony\Component\HttpFoundation\Response;

trait ApiTestsHelperMethodsTrait
{
    private function request(string $method, string $uri, array $params = []): Response
    {
        $this->client->request($method, $uri, [], [], [], json_encode($params));
        return $this->client->getResponse();
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