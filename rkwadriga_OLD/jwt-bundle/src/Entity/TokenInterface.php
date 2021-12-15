<?php
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Entity;

use DateTimeInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface TokenInterface
{
    public const ACCESS = 'access_token';
    public const REFRESH = 'refresh_token';

    public function getCreatedAt(): DateTimeInterface;

    public function getExpiredAt(): DateTimeInterface;

    public function getAccessToken(): string;

    public function getRefreshToken(): ?string;

    public function getUser(): ?UserInterface;
}