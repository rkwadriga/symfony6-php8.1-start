<?php declare(strict_types=1);
/**
 * Created 2021-12-03
 * Author Dmitry Kushneriov
 */

namespace App\Security;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class JwtUserProvider implements UserProviderInterface
{
    public function refreshUser(UserInterface $user)
    {
        dd($user);
    }

    public function supportsClass(string $class)
    {
        dd($class);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        dd($identifier);
    }

}