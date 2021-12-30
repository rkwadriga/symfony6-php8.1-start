<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Service;

use Exception;
use DateTimeImmutable;
use Rkwadriga\JwtBundle\DependencyInjection\Algorithm;
use Rkwadriga\JwtBundle\DependencyInjection\TokenGeneratorInterface;
use Rkwadriga\JwtBundle\DependencyInjection\HeadGeneratorInterface;
use Rkwadriga\JwtBundle\DependencyInjection\SerializerInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenInterface;
use Rkwadriga\JwtBundle\DependencyInjection\TokenType;
use Rkwadriga\JwtBundle\Entity\Token;
use Rkwadriga\JwtBundle\Enum\ConfigurationParam;
use Rkwadriga\JwtBundle\Enum\TokenCreationContext;
use Rkwadriga\JwtBundle\Event\TokenCreatingFinishedSuccessful;
use Rkwadriga\JwtBundle\Event\TokenCreatingFinishedUnsuccessful;
use Rkwadriga\JwtBundle\Event\TokenCreatingStarted;
use Rkwadriga\JwtBundle\Event\TokenParsingFinishedSuccessful;
use Rkwadriga\JwtBundle\Event\TokenParsingFinishedUnsuccessful;
use Rkwadriga\JwtBundle\Event\TokenParsingStarted;
use Rkwadriga\JwtBundle\Exception\TokenValidatorException;
use Rkwadriga\JwtBundle\Helpers\TimeHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TokenGenerator implements TokenGeneratorInterface
{
    public function __construct(
        private Config                      $config,
        private EventDispatcherInterface    $eventsDispatcher,
        private SerializerInterface         $serializer,
        private HeadGeneratorInterface      $headGenerator
    ) {}

    public function fromPayload(array $payload, TokenType $type, TokenCreationContext $creationContext, ?Algorithm $algorithm = null): TokenInterface
    {
        // This event can be used to change the payload
        $event = new TokenCreatingStarted($creationContext, $payload, $type);
        $this->eventsDispatcher->dispatch($event, $event::getName());
        $payload = $event->getPayload();
        $head = [];

        try {
            // Generate token signature and create token string
            $head = $this->headGenerator->generate($payload, $type);
            $content = $this->serializer->implode($this->serializer->serialize($head), $this->serializer->serialize($payload));
            $signature = $this->serializer->signature($content, $algorithm);
            $token = $this->serializer->implode($content, $this->serializer->encode($signature));

            // Get token life dates
            [$cratedAt, $expiredAt] = $this->lifePeriodFromPayload($payload, $type);
        } catch (Exception $e) {
            // This event can be used to change the error handling
            $event = new TokenCreatingFinishedUnsuccessful($creationContext, $e, $head, $payload, $type);
            $this->eventsDispatcher->dispatch($event, $event::getName());
            throw $event->getException();
        }

        $token = new Token($type, $token, $cratedAt, $expiredAt, $head, $payload, $signature);
        // This event can be used to change the token
        $event = new TokenCreatingFinishedSuccessful($creationContext, $token);
        $this->eventsDispatcher->dispatch($event, $event::getName());

        return $event->getToken();
    }

    public function fromString(string $token, TokenType $type): TokenInterface
    {
        // This event can be used to change the token
        $event = new TokenParsingStarted($token, $type);
        $this->eventsDispatcher->dispatch($event, $event::getName());
        $token = $event->getToken();
        [$head, $payload] = [[], []];

        try {
            // Get token head, payload and signature
            [$headString, $payloadString, $signature] = $this->serializer->explode($token);
            [$head, $payload] = [$this->serializer->deserialiaze($headString), $this->serializer->deserialiaze($payloadString)];

            // Check token type
            if (isset($head['sub']) && $head['sub'] !== $type->value) {
                throw new TokenValidatorException('Invalid token', TokenValidatorException::INVALID_TYPE);
            }

            // Check token signature
            $algorithm = isset($head['alg']) ? Algorithm::getByValue($head['alg']) : null;
            $content = $this->serializer->implode($headString, $payloadString);
            if ($this->serializer->decode($signature) !== $this->serializer->signature($content, $algorithm)) {
                throw new TokenValidatorException('Invalid token', TokenValidatorException::INVALID_SIGNATURE);
            }

            // Get token life dates
            [$cratedAt, $expiredAt] = $this->lifePeriodFromPayload($payload, $type);

            // Create token
            $token = new Token($type, $token, $cratedAt, $expiredAt, $head, $payload, $this->serializer->decode($signature));
        } catch (Exception $e) {
            // This event can be used to change the error handling
            $event = new TokenParsingFinishedUnsuccessful($e, $token, $type, $head, $payload);
            $this->eventsDispatcher->dispatch($event, $event::getName());
            throw $event->getException();
        }


        // This event can be used to change the token
        $event = new TokenParsingFinishedSuccessful($token);
        $this->eventsDispatcher->dispatch($event, $event::getName());
        return $event->getToken();
    }

    /**
     * @param array $payload
     * @param TokenType $type
     * @return array<DateTimeImmutable>
     */
    private function lifePeriodFromPayload(array $payload, TokenType $type): array
    {
        $timeStamp = $payload['created'] ?? time();
        $lifeTime = $type === TokenType::ACCESS
            ? $this->config->get(ConfigurationParam::ACCESS_TOKEN_LIFE_TIME)
            : $this->config->get(ConfigurationParam::REFRESH_TOKEN_LIFE_TIME);
        $cratedAt = TimeHelper::fromTimeStamp($timeStamp);
        $expiredAt = TimeHelper::addSeconds($lifeTime, clone $cratedAt);

        return [DateTimeImmutable::createFromInterface($cratedAt), DateTimeImmutable::createFromInterface($expiredAt)];
    }
}