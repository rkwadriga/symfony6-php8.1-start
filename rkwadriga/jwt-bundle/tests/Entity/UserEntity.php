<?php

namespace Rkwadriga\JwtBundle\Tests\Entity;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private string $id,
        private string $password,
        private array $roles = []
    ) {}

    public function getEmail(): string
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return 'email';
    }

    public function getRoles(): array
    {
        return array_unique(array_merge($this->roles, ['ROLE_USER']));
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function eraseCredentials()
    {
    }
}
