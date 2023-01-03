<?php

namespace App\Dto;

use App\Service\JwtDecoder;
use JMS\Serializer\Annotation as Serializer;

class UserDto
{
    #[Serializer\Type('string')]
    public string $username;

    #[Serializer\Type('string')]
    public string $password;

    #[Serializer\Type('array')]
    public array $roles;

    #[Serializer\Type('float')]
    public float $balance;

    public static function fromToken(string $token): self
    {
        $user = new self();

        $jwtDecode = new JwtDecoder();
        $jwtDecode->decode($token);

        $user->username = $jwtDecode->getUsername();
        $user->password = '';
        $user->roles = $jwtDecode->getRoles();
        $user->balance = 0;

        return $user;
    }
}
