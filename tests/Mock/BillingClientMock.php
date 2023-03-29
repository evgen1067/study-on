<?php

namespace App\Tests\Mock;

use App\DTO\Request\UserRequestDTO;
use App\DTO\Response\TokenResponseDTO;
use App\DTO\Response\UserResponseDTO;
use App\Exception\BillingException;
use App\Security\User;
use App\Service\BillingClient;
use JMS\Serializer\SerializerInterface;

class BillingClientMock extends BillingClient
{
    private array $user;

    private array $admin;

    public function __construct(SerializerInterface $serializer)
    {
        parent::__construct($serializer);

        $this->user = [
            'username' => 'user@study-on.ru',
            'password' => 'password',
            'roles' => ['ROLE_USER'],
            'balance' => 5000.0,
        ];

        $this->admin = [
            'username' => 'admin@study-on.ru',
            'password' => 'password',
            'roles' => ['ROLE_USER', 'ROLE_SUPER_ADMIN'],
            'balance' => 5000.0,
        ];
    }

    public function auth($credentials): User
    {
        $credentials = json_decode($credentials, true, 512, JSON_THROW_ON_ERROR);
        $username = $credentials['username'];
        $password = $credentials['password'];
        $tokenDto = new TokenResponseDTO();
        if ($username === $this->user['username'] && $password === $this->user['password']) {
            $tokenDto->token = $this->generateToken($this->user['roles'], $username);
            return User::fromDto($tokenDto);
        } elseif ($username === $this->admin['username'] && $password === $this->admin['password']) {
            $tokenDto->token = $this->generateToken($this->admin['roles'], $username);
            return User::fromDto($tokenDto);
        } else {
            throw new BillingException('Ошибка авторизации. Проверьте правильность введенных данных!');
        }
    }

    public function register($credentials): User
    {
        $userDto = $this->serializer->deserialize($credentials, UserRequestDTO::class, 'json');
        $username = $userDto->username;
        $password = $userDto->password;
        $tokenDto = new TokenResponseDTO();
        if ($username === $this->admin['username'] || $username === $this->user['username']) {
            throw new BillingException('Email уже используется.');
        }
        $tokenDto->token = $this->generateToken($this->user['roles'], $username);
        return User::fromDto($tokenDto);
    }

    public function profile(string $jwtToken): UserResponseDTO
    {
        $tokenDTO = new TokenResponseDTO();
        $tokenDTO->token = $jwtToken;
        $u = User::fromDTO($tokenDTO);
        $dto = new UserResponseDTO();
        $dto->username = $u->getEmail();
        $dto->roles = $u->getRoles();
        if ($u->getEmail() === $this->user['username']) {
            $dto->balance = $this->user['balance'];
        } elseif ($u->getEmail() === $this->admin['username']) {
            $dto->balance = $this->admin['balance'];
        }
        return $dto;
    }

    private function generateToken(array $roles, string $username): string
    {
        $data = [
            'email' => $username,
            'roles' => $roles,
            'exp' => (new \DateTime('+ 1 hour'))->getTimestamp(),
        ];
        $query = base64_encode(json_encode($data));

        return 'header.' . $query . '.signature';
    }
}
