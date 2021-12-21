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

trait InstanceTokenTrait
{
    /**
     * @return array<TokenInterface>
     */
    protected function createTokensPair(
        Algorithm $algorithm,
        string $userID = self::USER_ID,
        ?int $createdAt = null
    ): array {
        $accessToken = $this->createToken($algorithm, TokenType::ACCESS, $userID, $createdAt);
        return [
            $accessToken,
            $this->createToken($algorithm, TokenType::REFRESH, $userID, $accessToken->getPayload()['created']),
        ];
    }

    protected function createToken(
        Algorithm $algorithm,
        TokenType $type,
        string $userID = self::USER_ID,
        ?int $createdAt = null
    ): TokenInterface {
        if ($createdAt === null) {
            $createdAt = time();
        }
        [$createdAtDateTime, $expiredAtDateTime] = $this->getRefreshTokenLifeTime($createdAt, $type);
        [$head, $payload] = [$this->getRefreshTokenHead($algorithm, $type), $this->getRefreshTokenPayload($createdAt, $userID)];
        [$headString, $payloadString] = [$this->encodeRefreshTokenData($head), $this->encodeRefreshTokenData($payload)];
        $signature = $this->getRefreshTokenSignature($algorithm, $head, $payload);

        return new Token(
            $type,
            $this->implodeRefreshTokenParts($headString, $payloadString, $this->encodeRefreshTokenPart($signature)),
            $createdAtDateTime,
            $expiredAtDateTime,
            $head,
            $payload,
            $signature
        );
    }

    /**
     * @return array<DateTimeImmutable>
     */
    protected function getRefreshTokenLifeTime(int $createdAt, TokenType $tokenType): array
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

    protected function getRefreshTokenHead(Algorithm $algorithm, TokenType $type): array
    {
        return [
            'alg' => $algorithm->value,
            'typ' => 'JWT',
            'sub' => $type->value,
        ];
    }

    protected function getRefreshTokenPayload(int $createdAt, string $userID): array
    {
        return [
            'created' => $createdAt,
            'email' => $userID,
        ];
    }

    protected function encodeRefreshTokenPart(string $data): string
    {
        return str_replace('=', '', base64_encode($data));
    }

    protected function encodeRefreshTokenData(array $data): string
    {
        return $this->encodeRefreshTokenPart(json_encode($data));
    }

    protected function getRefreshTokenSignature(Algorithm $algorithm, array $head, array $payload): string
    {
        $contentPart = $this->implodeRefreshTokenParts($this->encodeRefreshTokenData($head), $this->encodeRefreshTokenData($payload));

        return hash_hmac($algorithm->value, $contentPart, self::SECRET_KEY);
    }

    protected function implodeRefreshTokenParts(string ...$parts): string
    {
        return implode('.', $parts);
    }
}