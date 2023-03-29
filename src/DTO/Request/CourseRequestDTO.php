<?php

namespace App\DTO\Request;

use JMS\Serializer\Annotation as Serializer;

class CourseRequestDTO
{
    #[Serializer\Type('string')]
    public string $type;

    #[Serializer\Type('string')]
    public string $title;

    #[Serializer\Type('string')]
    public string $code;

    #[Serializer\Type('float')]
    public float $price;
}