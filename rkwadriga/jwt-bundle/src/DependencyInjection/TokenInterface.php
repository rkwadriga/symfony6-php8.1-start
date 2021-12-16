<?php
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

use DateTimeInterface;

interface TokenInterface
{
    public function getType(): TokenType;

    public function getToken(): string;

    public function getCreatedAt(): DateTimeInterface;

    public function getExpiredAt(): DateTimeInterface;
}