<?php declare(strict_types=1);
/**
 * Created 2021-12-27
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Security;

use Doctrine\ORM\EntityManagerInterface;
use Rkwadriga\JwtBundle\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    public function __construct(
        private string $classOrAlias,
        private EntityManagerInterface $em,
    ) {}

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return $this->classOrAlias === $class;
    }

    /**
     * @param string $identifier
     * @return UserInterface
     *
     * @throws UserNotFoundException
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        /** @var UserRepository $repository */
        $repository = $this->em->getRepository($this->classOrAlias);
        $user = $repository->find($identifier);
        if ($user === null) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        return $user;
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }
}