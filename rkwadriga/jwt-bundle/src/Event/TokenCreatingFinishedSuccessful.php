<?php declare(strict_types=1);
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Rkwadriga\JwtBundle\DependencyInjection\TokenInterface;
use Rkwadriga\JwtBundle\Enum\TokenCreationContext;

class TokenCreatingFinishedSuccessful extends AbstractTokenCreatingEvent
{
    protected static string $name = 'rkwadriga.jwt.token_creating_finished_successful';

    public function __construct(
        TokenCreationContext $creationContext,
        private TokenInterface $token
    ) {
        parent::__construct($creationContext);
    }

    public function getToken(): TokenInterface
    {
        return $this->token;
    }

    public function setToken(TokenInterface $token): void
    {
        $this->token = $token;
    }
}