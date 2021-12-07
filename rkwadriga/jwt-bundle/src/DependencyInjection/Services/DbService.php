<?php declare(strict_types=1);
/**
 * Created 2021-12-07
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection\Services;

class DbService
{
    public function __construct(
        private bool $isEnabled,
        private string $table,
        private int $tokensLimit
    ) {}

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }
}