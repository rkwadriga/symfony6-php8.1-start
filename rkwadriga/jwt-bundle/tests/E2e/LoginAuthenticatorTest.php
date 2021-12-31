<?php declare(strict_types=1);
/**
 * Created 2021-12-22
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\E2e;

use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Entity\Token;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Tests\InstanceTokenTrait;
use Rkwadriga\JwtBundle\Tests\RefreshTokenTrait;
use Rkwadriga\JwtBundle\Tests\UserInstanceTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/E2e/LoginAuthenticatorTest.php
 */
class LoginAuthenticatorTest extends AbstractE2eTestCase
{
    use UserInstanceTrait;
    use InstanceTokenTrait;
    use RefreshTokenTrait;

    public function testSuccessfulLogin(): void
    {
        // Crate user
        $user = $this->createUser();

        // Do not forget to clear the refresh tokens table
        $this->clearRefreshTokenTable();

        // Login the user
        $this->login();

        $this->checkTokenResponse($user);
    }

    public function testInvalidCredentialsException(): void
    {
        // Check not existed user login
        $this->login();
        $this->checkErrorResponse(Response::HTTP_FORBIDDEN, 'Bad credentials');

        // Create user and check invalid login and password
        $user = $this->createUser();

        $this->login('INVALID_EMAIL');
        $this->checkErrorResponse(Response::HTTP_FORBIDDEN, 'Bad credentials');

        $this->login($user->getEmail(), 'INVALID_PASSWORD');
        $this->checkErrorResponse(Response::HTTP_FORBIDDEN, 'Bad credentials');

        $this->login('INVALID_EMAIL', 'INVALID_PASSWORD');
        $this->checkErrorResponse(Response::HTTP_FORBIDDEN, 'Bad credentials');
    }

    public function testMissedRequiredParamsException(): void
    {
        // Create user and check login without required fields
        $user = $this->createUser();

        $this->login(false);
        $this->checkErrorResponse(Response::HTTP_FORBIDDEN, ['Params', 'required']);

        $this->login($user->getEmail(), false);
        $this->checkErrorResponse(Response::HTTP_FORBIDDEN, ['Params', 'required']);

        $this->login(false, false);
        $this->checkErrorResponse(Response::HTTP_FORBIDDEN, ['Params', 'required']);
    }

    public function testDeletingOldestDbRecord(): void
    {
        // Do not forget to clear the refresh tokens table
        $this->clearRefreshTokenTable();

        // Crate user
        $user = $this->createUser();

        // Check is writing to DB possible
        $limit = $this->getConfigDefault(ConfigurationParam::REFRESH_TOKENS_LIMIT);
        if ($limit === 0 || !$this->getConfigDefault(ConfigurationParam::REFRESH_TOKEN_IN_DB)) {
            $this->assertSame(1, 1);
            return;
        }

        // Write refresh tokens to DB
        $algorithm = Algorithm::from($this->getConfigDefault(ConfigurationParam::ENCODING_ALGORITHM));
        $created = time();
        /** @var ?Token $oldestToken */
        $oldestToken = null;
        /** @var array<Token> $notOldestTokens */
        $notOldestTokens = [];
        for ($i = 1; $i <= $limit; $i++) {
            $refreshToken = $this->createToken($algorithm, TokenType::REFRESH, $user->getEmail(), $created + $i + 10, true);
            if ($oldestToken === null) {
                $oldestToken = $refreshToken;
            } else {
                $notOldestTokens[] = $refreshToken;
            }
        }

        $this->entityManager->flush();

        // Login the user
        $this->login();

        // Check that oldest token is not presented in DB
        $oldestTokenInDb = $this->findRefreshTokenBy($algorithm, ['userId' => $user->getEmail(), 'refreshToken' => $oldestToken->getSignature()]);
        $this->assertNull($oldestTokenInDb);

        // Check token params and check refresh token in DB
        $tokenParts = $this->explodeToken($this->getResponseParams('refreshToken'));
        $signature = $this->decodeTokenPart(end($tokenParts));
        $tokenInDb = $this->findRefreshTokenBy($algorithm, ['userId' => $user->getEmail(), 'refreshToken' => $signature]);
        $this->assertNotNull($tokenInDb);

        // Check that not the oldest tokens are presented in DB
        foreach ($notOldestTokens as $notOldestToken) {
            $tokenInDb = $this->findRefreshTokenBy($algorithm, ['userId' => $user->getEmail(), 'refreshToken' => $notOldestToken->getSignature()]);
            $this->assertNotNull($tokenInDb);
        }
    }
}