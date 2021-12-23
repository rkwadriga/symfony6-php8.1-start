<?php declare(strict_types=1);
/**
 * Created 2021-12-21
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Rkwadriga\JwtBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/Unit/PayloadGeneratorTest.php
 */
class PayloadGeneratorTest extends AbstractUnitTestCase
{
    public function testGenerate(): void
    {
        $user = new User('test_user@mail.com', 'passwd', ['TEST_ROLE']);
        $authToken = $this->createMock(PostAuthenticationToken::class, ['getUser' => $user, 'getUserIdentifier' => 'email']);
        $request = $this->createMock(Request::class);

        $payload = $this->createPayloadGeneratorInstance()->generate($authToken, $request);
        $this->assertArrayHasKey('created', $payload);
        $this->assertSame(time(), $payload['created']);
        $this->assertArrayHasKey('email', $payload);
        $this->assertSame($user->getEmail(), $payload['email']);
    }
}