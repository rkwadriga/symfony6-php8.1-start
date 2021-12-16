<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

use Rkwadriga\JwtBundle\Enum\AuthenticationType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class AuthenticationStarted extends AbstractAuthenticationEvent
{
    protected static string $name = 'rkwadriga.jwt.authentication_started';

    public function __construct(
        AuthenticationType $authenticationType,
        private Request $request,
        private ?Passport $passport = null
    ) {
        parent::__construct($authenticationType);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getPassport(): ?Passport
    {
        return $this->passport;
    }

    public function setPassport(Passport $passport)
    {
        $this->passport = $passport;
    }
}