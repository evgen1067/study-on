<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Tests\AbstractTest;

class CourseControllerTest extends AbstractTest
{
    /**
     * @return string[]
     */
    public function getFixtures(): array
    {
        return [AppFixtures::class];
    }
}
