<?php

namespace App\DTO\Request;

use JMS\Serializer\Annotation as Serializer;

class UserRequestDTO
{
    #[Serializer\Type('string')]
    public string $username;

    #[Serializer\Type('string')]
    public string $password;
}