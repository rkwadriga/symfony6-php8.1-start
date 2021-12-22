<?php declare(strict_types=1);
/**
 * Created 2021-12-22
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/Unit/TokenResponseCreatorTest.php
 */
class TokenResponseCreatorTest extends AbstractUnitTestCase
{
    public function testCreate(): void
    {
        // Create "TokenResponseCreator" service instance
        $tokenResponseCreatorService = $this->createTokenResponseCreatorInstance();

        // For all encoding algorithms...
        foreach (Algorithm::cases() as $algorithm) {
            [$accessToken, $refreshToken] = $this->createTokensPair($algorithm);
            $tokensResponse = $tokenResponseCreatorService->create($accessToken, $refreshToken);
            $this->assertIsArray($tokensResponse);
            $this->assertArrayHasKey('accessToken', $tokensResponse);
            $this->assertArrayHasKey('refreshToken', $tokensResponse);
            $this->assertArrayHasKey('expiredAt', $tokensResponse);
            $this->assertSame($accessToken->getToken(), $tokensResponse['accessToken']);
            $this->assertSame($refreshToken->getToken(), $tokensResponse['refreshToken']);
            $this->assertEquals($accessToken->getExpiredAt(), $tokensResponse['expiredAt']);
        }
    }
}