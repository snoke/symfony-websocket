<?php

namespace Snoke\Websocket\Security;

use React\Socket\ConnectionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class Authenticator
{
    private UserPasswordHasherInterface $passwordHasher;
    private UserProviderInterface $userProvider;

    public function __construct(UserPasswordHasherInterface $passwordHasher, UserProviderInterface $userProvider) {
        $this->passwordHasher = $passwordHasher;
        $this->userProvider = $userProvider;
    }

    public function authenticate(ConnectionInterface $connection, string $identifier, string $password): ?UserInterface {
        $user = $this->userProvider->loadUserByIdentifier($identifier);
        if ($this->passwordHasher->isPasswordValid($user, $password)) {
            return $user;
        };
        return null;
    }

}