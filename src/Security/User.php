<?php

namespace App\Security;

use App\DTO\TokenResponseDTO;
use DateTime;
use JsonException;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    private ?string $email = null;

    private array $roles = [];

    private ?string $apiToken = null;

    private ?string $password = null;

    private ?DateTime $exp = null;

    public function getExp(): ?DateTime
    {
        return $this->exp;
    }

    public function setExp(DateTime $exp): self
    {
        $this->exp = $exp;

        return $this;
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(string $apiToken): self
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
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

    /**
     * @throws JsonException
     */
    public static function fromDTO(TokenResponseDTO $tokenDTO): self
    {
        $u = new self();
        [$exp, $email, $roles] = self::jwtDecode($tokenDTO->token);
        $u
            ->setEmail($email)
            ->setApiToken($tokenDTO->token)
            ->setRoles($roles)
            ->setExp((new DateTime())->setTimestamp($exp));
        return $u;
    }

    /**
     * @throws JsonException
     */
    public static function jwtDecode(string $token): array
    {
        $parts = explode('.', $token);
        $payload = json_decode(base64_decode($parts[1]), true, 512, JSON_THROW_ON_ERROR);
        return [$payload['exp'], $payload['email'], $payload['roles']];
    }
}
