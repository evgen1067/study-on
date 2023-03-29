<?php

namespace App\Security;

use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use DateTime;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private BillingClient $billingClient;

    public function __construct(BillingClient $billingClient)
    {
        $this->billingClient = $billingClient;
    }

    public function loadUserByIdentifier($identifier): UserInterface
    {
        return (new User())->setEmail($identifier);
    }

    /**
     * Refreshes the user after being reloaded from the session.
     *
     * When a user is logged in, at the beginning of each request, the
     * User object is loaded from the session and then this method is
     * called. Your job is to make sure the user's data is still fresh by,
     * for example, re-querying for fresh User data.
     *
     * If your firewall is "stateless: true" (for a pure API), this
     * method is not called.
     * @throws \JsonException
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        [$exp, $email, $roles] = User::jwtDecode($user->getApiToken());
        $exp = (new DateTime())->setTimestamp($exp);
        $time = (new DateTime())->add(new \DateInterval('PT5M'));

        if ($time >= $exp) {
            $refreshToken = json_encode([
                'refresh_token' => $user->getRefreshToken()
            ]);
            try {
                $newData = $this->billingClient->refresh($refreshToken);
                $user->setApiToken($newData->getApiToken());
                $user->setRefreshToken($newData->getRefreshToken());
            } catch (BillingException | BillingUnavailableException | \JsonException $e) {
                throw new \RuntimeException($e->getMessage());
            }
        }

        return $user;
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    /**
     * Upgrades the hashed password of a user, typically for using a better hash algorithm.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        // TODO: when hashed passwords are in use, this method should:
        // 1. persist the new password in the user storage
        // 2. update the $user object with $user->setPassword($newHashedPassword);
    }
}
