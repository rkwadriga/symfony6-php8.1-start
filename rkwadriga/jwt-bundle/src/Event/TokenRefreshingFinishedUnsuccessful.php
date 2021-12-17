<?php declare(strict_types=1);
/**
 * Created 2021-12-17
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Exception;
use Rkwadriga\JwtBundle\DependencyInjection\TokenInterface;

class TokenRefreshingFinishedUnsuccessful extends AbstractTokenRefreshingEvent
{
    protected static string $name = 'rkwadriga.jwt.token_refreshing_finished_unsuccessful';

    public function __construct(
        TokenInterface $oldRefreshToken,
        TokenInterface $newRefreshToken,
        TokenInterface $accessToken,
        private Exception $exception
    ) {
        parent::__construct($oldRefreshToken, $newRefreshToken, $accessToken);
    }

    public function getException(): Exception
    {
        return $this->exception;
    }

    public function setException(Exception $exception): void
    {
        $this->exception = $exception;
    }
}