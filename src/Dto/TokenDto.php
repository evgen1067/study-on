<?php

namespace App\Dto;

use JMS\Serializer\Annotation as Serializer;

class TokenDto
{
    #[Serializer\Type('string')]
    public string $token;
}
