<?php
/**
 * Created 2021-12-16
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\DependencyInjection;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;

interface PayloadGeneratorInterface
{
    public function generate(TokenInterface $token, Request $request): array;
}