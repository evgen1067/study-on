<?php

namespace App\DTO\Response;

use JMS\Serializer\Annotation as Serializer;

class TokenResponseDTO
{
    #[Serializer\Type('string')]
    public string $token;

    #[Serializer\Type('string')]
    public string $refresh_token;
}