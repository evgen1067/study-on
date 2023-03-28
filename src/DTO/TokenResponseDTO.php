<?php

namespace App\DTO;

use JMS\Serializer\Annotation as Serializer;

class TokenResponseDTO
{
    #[Serializer\Type('string')]
    public string $token;
}