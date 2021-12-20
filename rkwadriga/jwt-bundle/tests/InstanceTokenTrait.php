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

trait InstanceTokenTrait
{
    /**
     * @return array<TokenInterface>
     */
    protected function createTokensPair(
        Algorithm $algorithm,
        string $userID = self::USER_ID,
        ?int $createdAt = null,
        ?int $lifeTime = null
    ): array {
        $accessToken = $this->createToken($algorithm, TokenType::ACCESS, $userID, $createdAt, $lifeTime);
        return [
            $accessToken,
            $this->createToken($algorithm, TokenType::REFRESH, $userID, $accessToken->getPayload()['created']),
        ];
    }

    protected function createToken(
        Algorithm $algorithm,
        TokenType $type,
        string $userID = self::USER_ID,
        ?int $createdAt = null,
        ?int $lifeTime = null,
    ): TokenInterface {
        if ($createdAt === null) {
            $createdAt = time();
        }
        if ($lifeTime === null) {
            $lifeTime = $type === TokenType::ACCESS ? 3600 : 15552000;
        }

        [$createdAtDateTime, $expiredAtDateTime] = $this->getLifeTime($createdAt, $lifeTime);
        [$head, $payload] = [$this->getHead($algorithm, $type), $this->getPayload($createdAt, $userID)];
        [$headString, $payloadString] = [$this->encode($head), $this->encode($payload)];
        $signature = $this->getSignature($algorithm, $head, $payload);

        return new Token(
            $type,
            $headString . '.' . $payloadString . '.' . $signature,
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
    protected function getLifeTime(int $createdAt, int $lifeTime): array
    {
        $createdAtDateTime = new DateTime();
        $createdAtDateTime->setTimestamp($createdAt);
        $expiredAtDateTime = clone $createdAtDateTime;
        $expiredAtDateTime->add(DateInterval::createFromDateString($lifeTime . ' seconds'));

        return [
            DateTimeImmutable::createFromMutable($createdAtDateTime),
            DateTimeImmutable::createFromMutable($expiredAtDateTime),
        ];
    }

    protected function getHead(Algorithm $algorithm, TokenType $type): array
    {
        return [
            'alg' => $algorithm->value,
            'typ' => 'JWT',
            'sub' => $type->value,
        ];
    }

    protected function getPayload(int $createdAt, string $userID): array
    {
        return [
            'created' => $createdAt,
            'email' => $userID,
        ];
    }

    protected function encode(array $data): string
    {
        return str_replace('=', '', base64_encode(json_encode($data)));
    }

    protected function getSignature(Algorithm $algorithm, array $head, array $payload): string
    {
        [$headString, $payloadString] = [$this->encode($head), $this->encode($payload)];
        $contentPart = $headString . '.' . $payloadString;

        return hash_hmac($algorithm->value, $contentPart, self::SECRET_KEY);
    }
}