<?php

namespace App\DTO;

use JMS\Serializer\Annotation as Serializer;

class UserResponseDTO
{
    #[Serializer\Type('string')]
    public string $username;

    #[Serializer\Type('array')]
    public array $roles;

    #[Serializer\Type('float')]
    public float $balance;
}