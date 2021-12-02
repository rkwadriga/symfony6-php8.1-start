<?php declare(strict_types=1);
/**
 * Created 2021-12-02
 * Author Dmitry Kushneriov
 */

namespace App\Doctrine\Listeners;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class UserCreateOrUpdateListener
{
    public function __construct(
        private PasswordHasherFactoryInterface $encoder
    ) {}

    public function preFlush(User $user): void
    {
        if ($user->getPlainPassword() !== null) {
            $user->setPassword($this->encoder->getPasswordHasher($user)->hash($user->getPlainPassword()));
        }
    }
}