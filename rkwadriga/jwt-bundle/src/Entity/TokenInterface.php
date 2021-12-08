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
    public const ACCESS = 'access_token';
    public const REFRESH = 'refresh_token';

    public function getCreatedAt(): DateTime;

    public function getExpiredAt(): DateTime;

    public function getAccessToken(): string;

    public function getRefreshToken(): ?string;

    public function getUser(): ?UserInterface;
}