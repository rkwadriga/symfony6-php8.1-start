<?php declare(strict_types=1);
/**
 * Created 2021-12-09
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Rkwadriga\JwtBundle\Enum\AuthenticationType;

abstract class AbstractAuthenticationEvent extends AbstractEvent
{
    public function __construct(
        private AuthenticationType $authenticationType
    ) {}

    public function getAuthenticationType(): AuthenticationType
    {
        return $this->authenticationType;
    }
}