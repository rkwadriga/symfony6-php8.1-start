<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Rkwadriga\JwtBundle\DependencyInjection\TokenInterface;

class TokenParsingFinishedSuccessful extends AbstractTokenParsingEvent
{
    protected static string $name = 'rkwadriga.jwt.token_parsing_finished_successful';

    public function __construct(
        private TokenInterface $token
    ) {}

    public function getToken(): TokenInterface
    {
        return $this->token;
    }

    public function setToken(TokenInterface $token): void
    {
        $this->token = $token;
    }
}