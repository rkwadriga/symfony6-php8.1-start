<?php
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Entity;

use DateTime;
use Symfony\Component\Security\Core\User\UserInterface;

interface TokenInterface
{
    public function getUser(): ?UserInterface;

    public function getAccessToken(): string;

    public function getRefreshToken(): string;

    public function getExpiredAt(): DateTime;
}