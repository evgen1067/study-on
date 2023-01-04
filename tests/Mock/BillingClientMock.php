<?php

namespace App\Tests\Mock;

use App\Dto\TokenDto;
use App\Dto\UserDto;
use App\Exception\BillingException;
use App\Security\User;
use App\Service\BillingClient;
use DateTimeImmutable;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;

class BillingClientMock extends BillingClient
{
    private UserDto $user;

    private UserDto $admin;

    private array $courses;

    private array $transactions;

    public function __construct(SerializerInterface $serializer)
    {
        parent::__construct($serializer);

        $this->user = new UserDto();
        $this->user->username = 'user@study-on.ru';
        $this->user->password = 'password';
        $this->user->roles = ['ROLE_USER'];
        $this->user->balance = 5000.0 + 200.0;

        $this->admin = new UserDto();
        $this->admin->username = 'admin@study-on.ru';
        $this->admin->password = 'password';
        $this->admin->roles = ['ROLE_SUPER_ADMIN', 'ROLE_USER'];
        $this->admin->balance = 5000.0 + 2000.0;

        $this->courses = [
            [
                'code' => 'PHP-1',
                'type' => 1,
                'title' => 'Ключевые аспекты веб-разработки на PHP',
                'price' => 1000,
            ],
            [
                'code' => 'JS-1',
                'type' => 1,
                'title' => 'Основы JavaScript',
                'price' => 2000,
            ],
            [
                'code' => 'HTML-1',
                'type' => 2,
                'title' => 'Основы современной верстки',
                'price' => 0,
            ],
            [
                'code' => 'GIT-1',
                'type' => 3,
                'title' => 'Введение в Git',
                'price' => 2000,
            ],
            [
                'code' => 'OS-1',
                'type' => 3,
                'title' => 'Операционные системы',
                'price' => 1000,
            ],
        ];

        $this->transactions = [
            // deposit
            [
                'type' => 2,
                'amount' => 200,
                'customer' => $this->user,
                'created' => new DateTimeImmutable('2021-09-01 00:00:00'),
            ],
            [
                'type' => 2,
                'amount' => 2000,
                'customer' => $this->admin,
                'created' => new DateTimeImmutable('2021-10-01 00:00:00'),
            ],
            // buy
            [
                'type' => 1,
                'amount' => $this->courses[3]['price'],
                'course' => $this->courses[3],
                'customer' => $this->user,
                'created' => new DateTimeImmutable('2022-10-08 00:00:00'),
            ],
            [
                'type' => 1,
                'amount' => $this->courses[4]['price'],
                'course' => $this->courses[4],
                'customer' => $this->admin,
                'created' => new DateTimeImmutable('2022-10-10 00:00:00'),
            ],
            // rent - expires
            [
                'type' => 1,
                'amount' => $this->courses[0]['price'],
                'expires' => new \DateTimeImmutable('2024-09-27 00:00:00'),
                'course' => $this->courses[0],
                'customer' => $this->user,
                'created' => new \DateTimeImmutable('2022-09-20 00:00:00'),
            ],
            [
                'type' => 1,
                'amount' => $this->courses[0]['price'],
                'expires' => new \DateTimeImmutable('2022-10-17 00:00:00'),
                'course' => $this->courses[0],
                'customer' => $this->admin,
                'created' => new \DateTimeImmutable('2022-10-10 00:00:00'),
            ],
            [
                'type' => 1,
                'amount' => $this->courses[1]['price'],
                'expires' => new \DateTimeImmutable('2022-09-17 00:00:00'),
                'course' => $this->courses[1],
                'customer' => $this->user,
                'created' => new \DateTimeImmutable('2022-09-10 00:00:00'),
            ],
            [
                'type' => 1,
                'amount' => $this->courses[1]['price'],
                'expires' => new \DateTimeImmutable('2022-10-12 00:00:00'),
                'course' => $this->courses[1],
                'customer' => $this->admin,
                'created' => new \DateTimeImmutable('2022-10-05 00:00:00'),
            ],
            // rent
            [
                'type' => 1,
                'amount' => $this->courses[0]['price'],
                'expires' => new \DateTimeImmutable('2022-10-25 00:00:00'),
                'course' => $this->courses[0],
                'customer' => $this->user,
                'created' => new \DateTimeImmutable('2022-10-18 00:00:00'),
            ],
            [
                'type' => 1,
                'amount' => $this->courses[1]['price'],
                'expires' => new \DateTimeImmutable('2022-10-26 00:00:00'),
                'course' => $this->courses[1],
                'customer' => $this->admin,
                'created' => new \DateTimeImmutable('2022-10-19 00:00:00'),
            ],
        ];
    }

    public function auth($credentials): User
    {
        $credentials = json_decode($credentials, true, 512, JSON_THROW_ON_ERROR);
        $username = $credentials['username'];
        $password = $credentials['password'];
        $tokenDto = new TokenDto();
        if ($username === $this->user->username && $password === $this->user->password) {
            $tokenDto->token = $this->generateToken($this->user->roles, $username);
            $tokenDto->refresh_token = '666';

            return User::fromDto($tokenDto);
        } elseif ($username === $this->admin->username && $password === $this->admin->password) {
            $tokenDto->token = $this->generateToken($this->admin->roles, $username);
            $tokenDto->refresh_token = '666';

            return User::fromDto($tokenDto);
        } else {
            throw new BillingException('Ошибка авторизации. Проверьте правильность введенных данных!');
        }
    }

    public function register($data): User
    {
        $userDto = $this->serializer->deserialize($data, UserDto::class, 'json');
        $username = $userDto->username;
        $password = $userDto->password;
        $tokenDto = new TokenDto();
        if ($username === $this->admin->username || $username === $this->user->username) {
            throw new BillingException('Email уже используется.');
        }
        $tokenDto->token = $this->generateToken($this->user->roles, $username);
        $tokenDto->refresh_token = '666';

        return User::fromDto($tokenDto);
    }

    public function currentUser($token): UserDto
    {
        $userDto = UserDto::fromToken($token);
        if ($userDto->username === $this->user->username) {
            $userDto->balance = $this->user->balance;
        } elseif ($userDto->username === $this->admin->username) {
            $userDto->balance = $this->admin->balance;
        }

        return $userDto;
    }

    public function getTransactions($filters, $token)
    {
        if ('' === $token) {
            throw new AccessDeniedException();
        }

        $userDto = UserDto::fromToken($token);

        $transactions = $this->transactions;

        $transactions = array_filter($transactions, function ($transaction) use ($userDto) {
            return $transaction['customer']->username === $userDto->username;
        });

        if (isset($filters['type'])) {
            $transactions = array_filter($transactions, function ($transaction) use ($filters) {
                return $transaction['type'] === $filters['type'];
            });
        }

        if (isset($filters['course_code'])) {
            $transactions = array_filter($transactions, function ($transaction) use ($filters) {
                return $transaction['course_code'] === $filters['course_code'];
            });
        }

        if (isset($filters['skip_expired'])) {
            $transactions = array_filter($transactions, function ($transaction) {
                return !isset($transaction['expires_at']) || $transaction['expires_at'] > new \DateTimeImmutable();
            });
        }

        return $transactions;
    }

    private function generateToken(array $roles, string $username): string
    {
        $data = [
            'email' => $username,
            'roles' => $roles,
            'exp' => (new \DateTime('+ 1 hour'))->getTimestamp(),
        ];
        $query = base64_encode(json_encode($data));

        return 'header.'.$query.'.signature';
    }
}
