<?php

namespace App\Security;

use App\Dto\Response\UserAuthDto;
use App\Service\JwtDecoder;
use JsonException;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    private ?string $email = null;

    private array $roles = [];

    private ?string $apiToken = null;

    private ?string $password = null;

    /**
     * @throws JsonException
     */
    public static function fromDto(UserAuthDto $dto): self
    {
        $user = new self();

        $jwtDecode = new JwtDecoder();
        $jwtDecode->decode($dto->token);

        $user->setApiToken($dto->token);
        $user->setRoles($jwtDecode->getRoles());
        $user->setEmail($jwtDecode->getUsername());

        return $user;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(string $apiToken): void
    {
        $this->apiToken = $apiToken;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
