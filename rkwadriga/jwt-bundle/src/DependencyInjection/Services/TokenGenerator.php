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
        private string $encodingAlgorithm,
        private int $accessTokenLifeTime,
        private int $refreshTokenLifeTime
    ) {}

    public function generate(array $payload): Token
    {
        $accessToken = $this->generateAccessToken($payload);
        $expiredAt = $payload['exp'];
        $refreshToken = $this->generateRefreshToken($payload);

        return new Token($accessToken, $refreshToken, TimeHelper::fromTimeStamp($expiredAt));
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
        if (!isset($payload['exp'])) {
            $startTime = new DateTime();
            if (isset($payload['timestamp']) && is_numeric($payload['timestamp'])) {
                $startTime->setTimestamp((int)$payload['timestamp']);
            }
            $lifeTimeSeconds = $type === Token::ACCESS ? $this->accessTokenLifeTime : $this->refreshTokenLifeTime;
            $payload['exp'] = TimeHelper::addSeconds($lifeTimeSeconds, $startTime)->getTimestamp();
        }
        $header = ['alg' => $this->encodingAlgorithm, 'typ' => self::TOKEN_TYPE, 'sub' => $type];
        $contentPart = TokenHelper::toContentPartString($header, $payload);

        return TokenHelper::serialize($contentPart, $this->encoder->encode($contentPart));
    }
}