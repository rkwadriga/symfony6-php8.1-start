<?php
/**
 * Created 2021-12-20
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests;

use DateTime;
use DateInterval;
use DateTimeImmutable;
use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\TokenInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Entity\Token;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Service\HeadGenerator;
use Rkwadriga\JwtBundle\Tests\Entity\TokenTestPramsEntity;

trait InstanceTokenTrait
{
    /**
     * @return array<TokenInterface>
     */
    protected function createTokensPair(
        Algorithm $algorithm,
        ?string $userID = null,
        ?int $createdAt = null,
        bool $saveToDb = false
    ): array {
        if ($userID === null) {
            $userID = self::$userID;
        }
        $accessToken = $this->createToken($algorithm, TokenType::ACCESS, $userID, $createdAt);
        return [
            $accessToken,
            $this->createToken($algorithm, TokenType::REFRESH, $userID, $accessToken->getPayload()['created'], $saveToDb),
        ];
    }

    protected function createToken(
        Algorithm $algorithm,
        TokenType $type,
        ?string $userID = null,
        ?int $createdAt = null,
        bool $saveToDb = false
    ): TokenInterface {
        if ($userID === null) {
            $userID = self::$userID;
        }
        if ($createdAt === null) {
            $createdAt = time();
        }
        [$createdAtDateTime, $expiredAtDateTime] = $this->getTokenLifeTime($createdAt, $type);
        [$head, $payload] = [$this->getTokenHead($algorithm, $type), $this->getTokenPayload($createdAt, $userID)];
        [$headString, $payloadString] = [$this->encodeTokenData($head), $this->encodeTokenData($payload)];
        $signature = $this->getTokenSignature($algorithm, $head, $payload);

        $token = new Token(
            $type,
            $this->implodeTokenParts($headString, $payloadString, $this->encodeTokenPart($signature)),
            $createdAtDateTime,
            $expiredAtDateTime,
            $head,
            $payload,
            $signature
        );

        if ($saveToDb) {
            // Save token to DB
            $this->saveRefreshToken($token, $algorithm);
        }

        return $token;
    }

    protected function generateTestTokenParams(TokenType $tokenType, Algorithm $algorithm, ?int $created = null, ?string $userID = null): TokenTestPramsEntity
    {
        if ($created === null) {
            $created = time();
        }
        if ($userID === null) {
            $userID = $tokenType->value . '_' . $algorithm->value;
        }
        $head = ['alg' => $algorithm->value, 'typ' => HeadGenerator::TOKEN_TYPE, 'sub' => $tokenType->value];
        $payload = ['created' => $created, 'email' => $userID];
        [$headString, $payloadString] = [$this->encodeTokenData($head), $this->encodeTokenData($payload)];
        [$createdAtDateTime, $expiredAtDateTime] = $this->getTokenLifeTime($created, $tokenType);
        $contentPart = $this->implodeTokenParts($headString, $payloadString);
        $signature = $this->getTokenSignature($algorithm, $head, $payload);
        $encodedSignature = $this->encodeTokenPart($signature);
        $tokenString = $this->implodeTokenParts($contentPart, $encodedSignature);

        return new TokenTestPramsEntity(
            $created,
            $userID,
            $head,
            $payload,
            $headString,
            $payloadString,
            $createdAtDateTime,
            $expiredAtDateTime,
            $contentPart,
            $signature,
            $encodedSignature,
            $tokenString
        );
    }

    /**
     * @return array<DateTimeImmutable>
     */
    protected function getTokenLifeTime(int $createdAt, TokenType $tokenType): array
    {
        $lifeTime = $tokenType === TokenType::ACCESS
            ? $this->getConfigDefault(ConfigurationParam::ACCESS_TOKEN_LIFE_TIME)
            : $this->getConfigDefault(ConfigurationParam::REFRESH_TOKEN_LIFE_TIME);
        $createdAtDateTime = new DateTime();
        $createdAtDateTime->setTimestamp($createdAt);
        $expiredAtDateTime = clone $createdAtDateTime;
        $expiredAtDateTime->add(DateInterval::createFromDateString($lifeTime . ' seconds'));

        return [
            DateTimeImmutable::createFromMutable($createdAtDateTime),
            DateTimeImmutable::createFromMutable($expiredAtDateTime),
        ];
    }

    protected function getTokenHead(Algorithm $algorithm, TokenType $type): array
    {
        return [
            'alg' => $algorithm->value,
            'typ' => HeadGenerator::TOKEN_TYPE,
            'sub' => $type->value,
        ];
    }

    protected function getTokenPayload(int $createdAt, string $userID): array
    {
        return [
            'created' => $createdAt,
            'email' => $userID,
        ];
    }

    protected function encodeTokenPart(string $data): string
    {
        return str_replace('=', '', base64_encode($data));
    }

    protected function decodeTokenPart(string $data): string
    {
        return base64_decode($data);
    }

    protected function parseTokenPart(string $data): ?array
    {
        return json_decode($this->decodeTokenPart($data), true);
    }

    protected function encodeTokenData(array $data): string
    {
        return $this->encodeTokenPart(json_encode($data));
    }

    protected function getTokenSignature(Algorithm $algorithm, array $head, array $payload): string
    {
        $contentPart = $this->implodeTokenParts($this->encodeTokenData($head), $this->encodeTokenData($payload));
        $secretKey = $this->getConfigDefault(ConfigurationParam::SECRET_KEY);
        $encodingHashingCount = $this->getConfigDefault(ConfigurationParam::ENCODING_HASHING_COUNT);

        $result = hash_hmac($algorithm->value, $contentPart, $secretKey);
        for ($i = 1; $i < $encodingHashingCount; $i++) {
            $result = hash_hmac($algorithm->value, $i . $result, $secretKey . ':' . $i);
        }

        return $result;
    }

    protected function implodeTokenParts(string ...$parts): string
    {
        return implode('.', $parts);
    }

    protected function explodeToken(string $token): array
    {
        return explode('.', $token);
    }

    protected function createTokenResponseArray(Token $accessToken, Token $refreshToken): array
    {
        return [
            'accessToken' => $accessToken->getToken(),
            'refreshToken' => $refreshToken->getToken(),
            'expiredAt' => $accessToken->getExpiredAt(),
        ];
    }
}