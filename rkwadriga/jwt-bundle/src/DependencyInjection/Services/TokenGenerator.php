<?php declare(strict_types=1);
/**
 * Created 2021-12-05
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Services;

use DateTime;
use Rkwadriga\JwtBundle\Entities\Token;
use Rkwadriga\JwtBundle\Helpers\TimeHelper;
use Rkwadriga\JwtBundle\Helpers\TokenHelper;

class TokenGenerator
{
    private const TOKEN_TYPE = 'JWT';

    public function __construct(
        private Encoder $encoder,
        private int $accessTokenLifeTime,
        private int $refreshTokenLifeTime
    ) {}

    public function generate(array $payload): Token
    {
        $accessToken = $this->generateAccessToken($payload);
        // Remember access token expiration timestamp and delete it from payload
        // - generator will create a new one for access token
        $expiredAt = $payload['exp'];
        unset($payload['exp']);
        $refreshToken = $this->generateRefreshToken($payload);
        return new Token($accessToken, TimeHelper::fromTimeStamp($expiredAt), $refreshToken);
    }

    public function generateAccessToken(array &$payload): string
    {
        return $this->generateToken($payload, Token::ACCESS);
    }

    public function generateRefreshToken(array &$payload): string
    {
        return $this->generateToken($payload, Token::REFRESH);
    }

    private function generateToken(array &$payload, string $type): string
    {
        $this->preparePayload($payload, $type);
        $header = $this->createHeader($type);
        $contentPart = TokenHelper::toContentPartString($header, $payload);

        return TokenHelper::serialize($contentPart, $this->encoder->encode($contentPart));
    }

    private function preparePayload(array &$payload, string $type): void
    {
        if (!isset($payload['exp'])) {
            $startTime = new DateTime();
            if (isset($payload['timestamp']) && is_numeric($payload['timestamp'])) {
                $startTime->setTimestamp((int)$payload['timestamp']);
            }
            $lifeTimeSeconds = $type === Token::ACCESS ? $this->accessTokenLifeTime : $this->refreshTokenLifeTime;
            $payload['exp'] = TimeHelper::addSeconds($lifeTimeSeconds, $startTime)->getTimestamp();
        }
    }

    private function createHeader(string $type): array
    {
        return [
            'alg' => $this->encoder->getAlgorithm(),
            'typ' => self::TOKEN_TYPE,
            'sub' => $type
        ];
    }
}