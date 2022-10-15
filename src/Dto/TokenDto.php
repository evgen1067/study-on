<?php

namespace App\Dto;

use JMS\Serializer\Annotation as Serializer;

class TokenDto
{
    #[Serializer\Type('string')]
    public string $token;

    #[Serializer\Type('string')]
    public string $refresh_token;
}
