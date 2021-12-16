<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service;

use DateTimeImmutable;
use Rkwadriga\JwtBundle\DependencyInjection\TokenGeneratorInterface;
use Rkwadriga\JwtBundle\DependencyInjection\HeadGeneratorInterface;
use Rkwadriga\JwtBundle\DependencyInjection\SerializerInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Entity\Token;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Exception\TokenValidatorException;
use Rkwadriga\JwtBundle\Helpers\TimeHelper;

class TokenGenerator implements TokenGeneratorInterface
{
    public function __construct(
        private Config $config,
        private SerializerInterface    $serializer,
        private HeadGeneratorInterface $headGenerator
    ) {}

    public function fromPayload(array $payload, TokenType $type): TokenInterface
    {
        // Generate token signature and create token string
        $head = $this->headGenerator->generate($payload, $type);
        $content = $this->serializer->implode([$this->serializer->serialize($head), $this->serializer->serialize($payload)]);
        $signature = $this->serializer->signature($content);
        $token = $this->serializer->implode([$content, $signature]);

        // Get token life dates
        [$cratedAt, $expiredAt] = $this->lifeDatesFromPayload($payload, $type);

        return new Token(
            $type,
            $token,
            $cratedAt,
            $expiredAt
        );
    }

    public function fromString(string $token, TokenType $type): TokenInterface
    {
        // Get token parts
        [$headString, $payloadString, $signature] = $this->serializer->explode($token);
        // Check token signature
        $content = $this->serializer->implode([$headString, $payloadString]);
        if ($signature !== $this->serializer->signature($content)) {
            throw new TokenValidatorException('Invalid token', TokenValidatorException::INVALID_SIGNATURE);
        }

        // Get token head and payload
        [$head, $payload] = [$this->serializer->deserialiaze($headString), $this->serializer->deserialiaze($payloadString)];

        // Check token type
        if (isset($head['sub']) && $head['sub'] !== $type->value) {
            throw new TokenValidatorException('Invalid token', TokenValidatorException::INVALID_TYPE);
        }

        // Get token life dates
        [$cratedAt, $expiredAt] = $this->lifeDatesFromPayload($payload, $type);

        return new Token(
            $type,
            $token,
            $cratedAt,
            $expiredAt
        );
    }

    /**
     * @param array $payload
     * @return array<DateTimeImmutable>
     */
    private function lifeDatesFromPayload(array $payload, TokenType $type): array
    {
        $timeStamp = $payload['timestamp'] ?? time();
        $lifeTime = $type === TokenType::ACCESS
            ? $this->config->get(ConfigurationParam::ACCESS_TOKEN_LIFE_TIME)
            : $this->config->get(ConfigurationParam::REFRESH_TOKEN_LIFE_TIME);
        $cratedAt = TimeHelper::fromTimeStamp($timeStamp);
        $expiredAt = TimeHelper::addSeconds($lifeTime, clone $cratedAt);

        return [DateTimeImmutable::createFromInterface($cratedAt), DateTimeImmutable::createFromInterface($expiredAt)];
    }
}