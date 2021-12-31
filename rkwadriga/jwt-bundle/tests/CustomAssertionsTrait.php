<?php
/**
 * Created 2021-12-26
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use Exception;
use DateTime;
use DateInterval;
use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Entity\RefreshToken256;
use Rkwadriga\JwtBundle\Entity\RefreshToken512;
use Rkwadriga\JwtBundle\Entity\RefreshTokenEntityInterface;
use Rkwadriga\JwtBundle\Entity\User;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Service\HeadGenerator;
use Symfony\Component\HttpFoundation\Response;

trait CustomAssertionsTrait
{
    protected function compareExceptions(string $baseMessage, Exception $actual, Exception $expected, ?Exception $previous = null): void
    {
        $this->assertInstanceOf($expected::class, $actual,
            $baseMessage . 'Exception has an invalid type: ' . $actual::class
        );
        $this->assertSame($expected->getMessage(), $actual->getMessage(),
            $baseMessage . 'Exception has an invalid message: ' . $actual->getMessage()
        );
        $this->assertSame($expected->getCode(), $actual->getCode(),
            $baseMessage . 'Exception has an invalid code: ' . $actual->getCode()
        );
        if ($previous !== null) {
            $this->assertNotNull($actual->getPrevious(),
                $baseMessage . 'Exception has no previous exception'
            );
            $this->assertInstanceOf($previous::class, $actual->getPrevious(),
                $baseMessage . 'Exception previous has an invalid type: ' . $actual->getPrevious()::class
            );
        }
    }

    protected function checkTokenResponse(User $user, int $responseCode = Response::HTTP_CREATED): void
    {
        // Check response status code
        $this->assertResponseStatusCodeSame($responseCode);
        // Check response fields
        $responseParams = $this->getResponseParams();
        $tokenPattern = "/[\w|\d]+\.[\w|\d]+\.[\w|\d]+/";
        $this->assertArrayHasKey('accessToken', $responseParams);
        $this->assertIsString($responseParams['accessToken']);
        $this->assertMatchesRegularExpression($tokenPattern, $responseParams['accessToken']);
        $this->assertArrayHasKey('refreshToken', $responseParams);
        $this->assertIsString($responseParams['refreshToken']);
        $this->assertMatchesRegularExpression($tokenPattern, $responseParams['refreshToken']);
        $this->assertArrayHasKey('expiredAt', $responseParams);
        $this->assertIsString($responseParams['expiredAt']);
        try {
            new DateTime($responseParams['expiredAt']);
        } catch (Exception $e) {
            $this->assertSame(0, 1, "Token response has an incorrect value of \"expiredAt\": \"{$responseParams['expiredAt']}\"");
        }
        $accessTokenPayload = $refreshTokenPayload = [];

        foreach (['accessToken', 'refreshToken'] as $tokenName) {
            $tokenType = $tokenName === 'accessToken' ? TokenType::ACCESS : TokenType::REFRESH;

            // Check signature
            [$headString, $payloadString, $signatureString] = $this->explodeToken($responseParams[$tokenName]);
            [$head, $payload, $signature] = [
                $this->parseTokenPart($headString),
                $this->parseTokenPart($payloadString),
                $this->decodeTokenPart($signatureString),
            ];
            $userIDParam = $this->getConfigDefault(ConfigurationParam::USER_IDENTIFIER);
            $algorithm = $this->getConfigDefault(ConfigurationParam::ENCODING_ALGORITHM);
            $expectedSignature = $this->getTokenSignature(Algorithm::from($algorithm), $head, $payload);
            $this->assertSame($expectedSignature, $signature);

            // Remember tokens payload
            if ($tokenName === 'accessToken') {
                $accessTokenPayload = $payloadString;
            } else {
                $refreshTokenPayload = $payloadString;
            }

            // Check head and payload
            $this->assertNotNull($head);
            $this->assertNotNull($payload);
            $this->assertArrayHasKey('alg', $head);
            $this->assertArrayHasKey('typ', $head);
            $this->assertArrayHasKey('sub', $head);
            $this->assertSame($algorithm, $head['alg']);
            $this->assertSame(HeadGenerator::TOKEN_TYPE, $head['typ']);
            $this->assertSame($tokenType->value, $head['sub']);
            $this->assertArrayHasKey('created', $payload);
            $this->assertArrayHasKey($userIDParam, $payload);
            $this->assertIsInt($payload['created']);
            $this->assertSame($user->getEmail(), $payload[$userIDParam]);
        }

        // Refresh and access tokens mas have en equals payload
        $this->assertSame($accessTokenPayload, $refreshTokenPayload);

        // Check refresh token in DB
        /** @var RefreshTokenEntityInterface|null $refreshToken */
        $refreshTokenEntityClass = $algorithm === Algorithm::SHA256->value ? RefreshToken256::class : RefreshToken512::class;
        $refreshToken = $this->entityManager->getRepository($refreshTokenEntityClass)->findOneBy(['userId' => $user->getEmail(), 'refreshToken' => $signature]);
        $this->assertNotNull($refreshToken);

        // Check payload expiredAt
        $lifeTime = $this->getConfigDefault(ConfigurationParam::ACCESS_TOKEN_LIFE_TIME);
        $expiredAt = new DateTime($responseParams['expiredAt']);
        $createdAt = $expiredAt->add(DateInterval::createFromDateString("-{$lifeTime} seconds"));
        $this->assertEquals($createdAt, $refreshToken->getCreatedAt());
    }

    protected function checkErrorResponse(int $responseCode, mixed $errorMessage = null, ?int $errorCode = null): void
    {
        $this->assertResponseStatusCodeSame($responseCode);
        $responseParams = $this->getResponseParams();
        $this->assertIsArray($responseParams);
        $this->assertArrayHasKey('code', $responseParams);
        $this->assertArrayHasKey('message', $responseParams);
        $this->assertIsInt($responseParams['code']);
        $this->assertIsString($responseParams['message']);
        if ($errorMessage !== null) {
            if (is_string($errorMessage)) {
                $errorMessage = [$errorMessage];
            }
            foreach ($errorMessage as $message) {
                $this->assertStringContainsStringIgnoringCase($message, $responseParams['message']);
            }
        }
        if ($errorCode !== null) {
            $this->assertSame($errorCode, $responseParams['code']);
        }
    }
}