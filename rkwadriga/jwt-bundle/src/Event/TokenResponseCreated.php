<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

class TokenResponseCreated extends AbstractTokenEvent
{
    protected static string $name = 'rkwadriga.jwt.token_response_created';

    public function __construct(
        private array $tokenResponse
    ) {}

    public function getTokenResponse(): array
    {
        return $this->tokenResponse;
    }

    public function setTokenResponse(array $tokenResponse): void
    {
        $this->tokenResponse = $tokenResponse;
    }
}