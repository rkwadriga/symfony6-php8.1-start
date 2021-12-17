<?php
/**
 * Created 2021-12-17
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

use Rkwadriga\JwtBundle\Exception\TokenValidatorException;

interface TokenValidatorInterface
{
    /**
     * @throws TokenValidatorException
     */
    public function validate(TokenInterface $token, TokenType $tokenType, array $validationCases = [], array $validationCasesExcluding = []): void;

    /**
     * @throws TokenValidatorException
     */
    public function validateRefresh(TokenInterface $refreshToken, TokenInterface $accessToken): void;
}