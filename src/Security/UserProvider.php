<?php

namespace App\Security;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use App\Service\JwtDecoder;
use DateTime;
use Exception;
use JMS\Serializer\Serializer;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    private JwtDecoder $jwtDecoder;

    private BillingClient $billingClient;

    public function __construct(JwtDecoder $jwtDecoder, BillingClient $billingClient)
    {
        $this->jwtDecoder = $jwtDecoder;
        $this->billingClient = $billingClient;

    }

    /**
     * @throws Exception|UserNotFoundException if the user is not found
     */
    public function loadUserByIdentifier($identifier): UserInterface
    {
        $user = new User();
        $user->setEmail($identifier);

        return $user;
    }

    /**
     * @throws Exception
     *
     * @deprecated since Symfony 5.3, loadUserByIdentifier() is used instead
     */
    public function loadUserByUsername($username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    /**
     * @throws \JsonException
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }
        $this->jwtDecoder->decode($user->getApiToken());

        $exp = (new DateTime())->setTimestamp($this->jwtDecoder->getExp());
        $currentTime = (new DateTime())->add(new \DateInterval('PT3M')); # 3 minute

        if ($currentTime >= $exp) {
            try {
                $data = json_encode([
                    'refresh_token' => $user->getRefreshToken()
                ], JSON_THROW_ON_ERROR);
                $refresh = json_decode($this->billingClient->refreshToken($data), true, 512, JSON_THROW_ON_ERROR);
                $user->setApiToken($refresh['token']);
                $user->setRefreshToken($refresh['refresh_token']);
            } catch (BillingUnavailableException $e) {
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
    }
}
