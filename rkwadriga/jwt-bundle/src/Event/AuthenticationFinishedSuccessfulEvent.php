<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Event;

class AuthenticationFinishedSuccessfulEvent extends AbstractEvent
{
    public const NAME = 'rkwadriga_jwt_authentication_finished_successful';
}