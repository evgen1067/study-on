<?php

namespace App\Tests\Mock;

use App\Dto\UserDto;
use App\Service\BillingClient;
use JMS\Serializer\SerializerInterface;

class BillingClientMock extends BillingClient
{
    private UserDto $user;

    private UserDto $admin;

    public function __construct(SerializerInterface $serializer)
    {
        parent::__construct($serializer);

        $this->user = new UserDto();
        $this->user->username = 'user@study-on.ru';
        $this->user->password = 'password';
        $this->user->roles = ['ROLE_USER'];
        $this->user->balance = 5000.0;

        $this->admin = new UserDto();
        $this->admin->username = 'admin@study-on.ru';
        $this->admin->password = 'password';
        $this->admin->roles = ['ROLE_SUPER_ADMIN'];
        $this->admin->balance = 100000.0;
    }
}
