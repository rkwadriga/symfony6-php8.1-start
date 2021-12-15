<?php declare(strict_types=1);
/**
 * Created 2021-12-05
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Services;

use Exception;
use DateTime;
use DateTimeImmutable;
use Rkwadriga\JwtBundle\Entity\Token;
use Rkwadriga\JwtBundle\Entity\TokenInterface;
use Rkwadriga\JwtBundle\Event\TokenCreatingFinishedSuccessfulEvent;
use Rkwadriga\JwtBundle\Event\TokenCreatingFinishedUnsuccessfulEvent;
use Rkwadriga\JwtBundle\Event\TokenCreatingStartedEvent;
use Rkwadriga\JwtBundle\EventSubscriber\TokenCreateEventSubscriber;
use Rkwadriga\JwtBundle\Helpers\TimeHelper;
use Rkwadriga\JwtBundle\Helpers\TokenHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TokenGenerator
{
    private const TOKEN_TYPE = 'JWT';

    public function __construct(
        private EventDispatcherInterface $eventsDispatcher,
        private DbService $dbService,
        private Encoder $encoder,
        private int $accessTokenLifeTime,
        private int $refreshTokenLifeTime
    ) {
        $this->eventsDispatcher->addSubscriber(new TokenCreateEventSubscriber($this->dbService));
    }

    public function generate(array $payload): TokenInterface
    {
        // This event allows to change payload
        $event = new TokenCreatingStartedEvent($payload);
        $this->eventsDispatcher->dispatch($event, $event::getName());
        $payload = $event->getPayload();

        try {
            $token = $this->createToken($payload);

            // This event allows to change the token
            $event = new TokenCreatingFinishedSuccessfulEvent($token, $payload);
            $this->eventsDispatcher->dispatch($event, $event::getName());

            return $event->getToken();
        } catch (Exception $e) {
            // This event allow to process token creation exceptions
            $event = new TokenCreatingFinishedUnsuccessfulEvent($e, $payload);
            $this->eventsDispatcher->dispatch($event, $event::getName());
            throw $event->getException();
        }
    }

    public function createToken(array $payload): Token
    {
        $accessToken = $this->generateAccessToken($payload);
        // Remember access token expiration timestamp and delete it from payload
        // - generator will create a new one for access token
        $expiredAt = $payload['exp'];
        unset($payload['exp']);
        $refreshToken = $this->generateRefreshToken($payload);
        return new Token(
            isset($payload['timestamp'])
                ? DateTimeImmutable::createFromMutable(TimeHelper::fromTimeStamp($payload['timestamp']))
                : new DateTimeImmutable(),
            DateTimeImmutable::createFromMutable(TimeHelper::fromTimeStamp($expiredAt)),
            $accessToken,
            $refreshToken
        );
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