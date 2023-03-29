<?php

namespace App\Tests\Mock;

use App\DTO\Request\UserRequestDTO;
use App\DTO\Response\TokenResponseDTO;
use App\DTO\Response\UserResponseDTO;
use App\Exception\BillingException;
use App\Security\User;
use App\Service\BillingClient;
use DateInterval;
use DateTimeImmutable;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class BillingClientMock extends BillingClient
{
    private array $user;

    private array $admin;

    private array $courses;

    private array $transactions;

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
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P3Y')),
            ],
            [
                'type' => 2,
                'amount' => 2000,
                'customer' => $this->admin,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P3Y')),
            ],
            // buy
            [
                'type' => 1,
                'amount' => $this->courses[0]['price'],
                'course' => $this->courses[0],
                'customer' => $this->user,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P1Y3M6D')),
            ],
            [
                'type' => 1,
                'amount' => $this->courses[1]['price'],
                'course' => $this->courses[1],
                'customer' => $this->admin,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P1Y1M2D')),
            ],
            // rent - expires
            [
                'type' => 1,
                'amount' => $this->courses[3]['price'],
                'expires' => (new DateTimeImmutable())->sub(new DateInterval('P1Y3M6D')),
                'course' => $this->courses[3],
                'customer' => $this->user,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P1Y3M13D')),
            ],
            [
                'type' => 1,
                'amount' => $this->courses[3]['price'],
                'expires' => (new DateTimeImmutable())->sub(new DateInterval('P2Y3M6D')),
                'course' => $this->courses[3],
                'customer' => $this->admin,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P2Y3M13D')),
            ],
            [
                'type' => 1,
                'amount' => $this->courses[4]['price'],
                'expires' => (new DateTimeImmutable())->sub(new DateInterval('P2Y3M6D')),
                'course' => $this->courses[3],
                'customer' => $this->user,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P2Y3M13D')),
            ],
            [
                'type' => 1,
                'amount' => $this->courses[4]['price'],
                'expires' => (new DateTimeImmutable())->sub(new DateInterval('P1Y3M6D')),
                'course' => $this->courses[4],
                'customer' => $this->admin,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P1Y3M13D')),
            ],
            // rent
            [
                'type' => 1,
                'amount' => $this->courses[3]['price'],
                'expires' => (new DateTimeImmutable())->add(new DateInterval('P15D')),
                'course' => $this->courses[3],
                'customer' => $this->user,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P12D')),
            ],
            [
                'type' => 1,
                'amount' => $this->courses[4]['price'],
                'expires' => (new DateTimeImmutable())->add(new DateInterval('P16D')),
                'course' => $this->courses[4],
                'customer' => $this->admin,
                'created' => (new DateTimeImmutable())->sub(new DateInterval('P12D')),
            ],
        ];
    }

    public function auth($credentials): User
    {
        $credentials = json_decode($credentials, true, 512, JSON_THROW_ON_ERROR);
        $username = $credentials['username'];
        $password = $credentials['password'];
        $tokenDto = new TokenResponseDTO();
        $tokenDto->refresh_token = 'asd123asd456';
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
        $tokenDto->refresh_token = 'asd123asd456';
        $tokenDto->token = $this->generateToken($this->user['roles'], $username);
        return User::fromDto($tokenDto);
    }

    public function profile(string $jwtToken): UserResponseDTO
    {
        $tokenDTO = new TokenResponseDTO();
        $tokenDTO->token = $jwtToken;
        $tokenDTO->refresh_token = 'asd123asd456';
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

    public function courses(): array
    {
        return $this->courses;
    }

    public function course(string $code)
    {
        foreach ($this->courses as $course) {
            if ($course['code'] === $code) {
                return $course;
            }
        }

        return [];
    }

    public function history(array $filters, string $bearerToken): array
    {
        $tokenDTO = new TokenResponseDTO();
        $tokenDTO->token = $bearerToken;
        $tokenDTO->refresh_token = 'asd123asd456';
        $u = User::fromDTO($tokenDTO);
        if ($u->getEmail() !== $this->user['username'] && $u->getEmail() !== $this->admin['username']) {
            throw new AccessDeniedException();
        }
        $transactions = $this->transactions;
        // фильтруем по пользователю
        $transactions = array_filter($transactions, function ($transaction) use ($u) {
            return $transaction['customer']['username'] === $u->getEmail();
        });
        if (isset($filters['type'])) {
            $transactions = array_filter($transactions, function ($transaction) use ($filters) {
                return $transaction['type'] === $filters['type'];
            });
        }

        if (isset($filters['course_code'])) {
            $transactions = array_filter($transactions, function ($transaction) use ($filters) {
                return $transaction['course']['code'] === $filters['course_code'];
            });
        }

        if (isset($filters['skip_expired'])) {
            $transactions = array_filter($transactions, function ($transaction) {
                return !isset($transaction['expires']) || $transaction['expires'] > new \DateTimeImmutable();
            });
        }

        return $transactions;
    }

    public function newCourse(string $courseData, string $bearerToken): array
    {
        $tokenDTO = new TokenResponseDTO();
        $tokenDTO->token = $bearerToken;
        $tokenDTO->refresh_token = 'asd123asd456';
        $u = User::fromDTO($tokenDTO);
        if ($u->getEmail() !== $this->user['username'] && $u->getEmail() !== $this->admin['username']) {
            throw new AccessDeniedException();
        }
        $courseData = json_decode($courseData, true, 512, JSON_THROW_ON_ERROR);
        if (in_array($courseData['code'], array_column($this->courses, 'code'))) {
            throw new BillingException('Курс с таким кодом уже существует.');
        }

        $this->courses[] = $courseData;

        return [
            'success' => true,
        ];
    }

    public function editCourse(string $oldCourseCode, string $courseData, string $bearerToken): array
    {
        $tokenDTO = new TokenResponseDTO();
        $tokenDTO->token = $bearerToken;
        $tokenDTO->refresh_token = 'asd123asd456';
        $u = User::fromDTO($tokenDTO);
        if ($u->getEmail() !== $this->user['username'] && $u->getEmail() !== $this->admin['username']) {
            throw new AccessDeniedException();
        }

        $courseData = json_decode($courseData, true, 512, JSON_THROW_ON_ERROR);

        if (
            $oldCourseCode !== $courseData['code'] &&
            in_array($courseData['code'], array_column($this->courses, 'code'))
        ) {
            throw new BillingException('Курс с таким кодом уже существует.');
        }

        for ($i = 0; $i < count($this->courses); ++$i) {
            if ($oldCourseCode === $this->courses[$i]['code']) {
                $this->courses[$i] = $courseData;
            }
        }

        foreach ($this->courses as $key => $course) {
            if ($oldCourseCode === $course['code']) {
                $this->courses[$key] = $courseData;
            }
        }

        return [
            'success' => true,
        ];
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
