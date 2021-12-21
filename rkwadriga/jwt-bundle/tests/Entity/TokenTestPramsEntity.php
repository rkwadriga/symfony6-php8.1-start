<?php declare(strict_types=1);
/**
 * Created 2021-12-22
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Entity;

use DateTimeImmutable;

class TokenTestPramsEntity
{
    public function __construct(
        public int $created,
        public string $userID,
        public array $head,
        public array $payload,
        public string $headString,
        public string $payloadString,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $expiredAt,
        public string $contentPart,
        public string $signature,
        public string $encodedSignature,
        public string $tokenString,
    ) {}
}