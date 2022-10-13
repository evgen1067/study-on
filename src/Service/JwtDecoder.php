<?php

namespace App\Service;

use JsonException;

class JwtDecoder
{
    private string $username;

    private array $roles;

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @throws JsonException
     */
    public function decode($token): void
    {
        $parts = explode('.', $token);
        $payload = json_decode(base64_decode($parts[1]), true, 512, JSON_THROW_ON_ERROR);

        $this->username = $payload['email'];
        $this->roles = $payload['roles'];
    }
}
