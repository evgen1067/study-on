<?php

namespace App\Service;

use App\DTO\Response\TokenResponseDTO;
use App\DTO\Response\UserResponseDTO;
use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Exception\BillingValidationException;
use App\Security\User;
use JMS\Serializer\SerializerInterface;
use JsonException;

class BillingClient
{
    protected SerializerInterface $serializer;

    private array $routes = [
        'auth' => '/api/v1/auth',
        'refresh' => '/api/v1/token/refresh',
        'register' => '/api/v1/register',
        'profile' => '/api/v1/users/current',
        'courses' => '/api/v1/courses',
        'transactions' => '/api/v1/transactions',
    ];

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @throws BillingUnavailableException
     * @throws BillingException
     * @throws JsonException
     */
    public function auth($credentials): User
    {
        $response = (new ApiClient())->post(
            $this->routes['auth'],
            $credentials,
            [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            'Сервис временно недоступен.'
        );

        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        // выбрасываем исключение о том, что пользователь ввел неверные данные для авторизации
        if (isset($result['code'])) {
            if (401 === $result['code']) {
                throw new BillingException('Ошибка авторизации. Проверьте правильность введенных данных!');
            } elseif (200 !== $result['code']) {
                throw new BillingException($result['message']);
            }
        }
        $userDto = $this->serializer->deserialize($response, TokenResponseDTO::class, 'json');
        return User::fromDto($userDto);
    }

    /**
     * @throws BillingUnavailableException
     * @throws BillingValidationException
     * @throws BillingException
     * @throws JsonException
     */
    public function register($credentials): User
    {
        $response = (new ApiClient())->post(
            $this->routes['register'],
            $credentials,
            [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            'Сервис временно недоступен.'
        );

        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (isset($result['code'])) {
            if (409 === $result['code']) {
                throw new BillingException($result['message']);
            }
            if (400 === $result['code']) {
                throw new BillingValidationException(json_decode($result['errors']));
            }
        }
        $userDto = $this->serializer->deserialize($response, TokenResponseDTO::class, 'json');
        return User::fromDto($userDto);
    }

    /**
     * @throws BillingUnavailableException
     * @throws BillingException
     * @throws JsonException
     */
    public function profile(string $jwtToken): UserResponseDTO
    {
        $response = (new ApiClient())->get(
            $this->routes['profile'],
            null,
            [
                'Authorization: Bearer ' . $jwtToken,
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            'Сервис временно недоступен.'
        );
        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (isset($result['message'])) {
            throw new BillingException($result['message']);
        }
        return $this->serializer->deserialize($response, UserResponseDTO::class, 'json');
    }

    /**
     * @throws BillingUnavailableException
     * @throws BillingException
     * @throws JsonException
     */
    public function refresh(string $refreshToken): User
    {
        $response = (new ApiClient())->post(
            $this->routes['refresh'],
            $refreshToken,
            [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            'Сервис временно недоступен.'
        );

        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        // выбрасываем исключение о том, что пользователь ввел неверные данные для авторизации
        if (isset($result['code'])) {
            if (401 === $result['code']) {
                throw new BillingException('Ошибка авторизации. Проверьте правильность введенных данных!');
            } elseif (200 !== $result['code']) {
                throw new BillingException($result['message']);
            }
        }
        $userDto = $this->serializer->deserialize($response, TokenResponseDTO::class, 'json');
        return User::fromDto($userDto);
    }

    /**
     * @throws BillingUnavailableException
     * @throws BillingException
     * @throws JsonException
     */
    public function courses(): array
    {
        $response = (new ApiClient())->get(
            $this->routes['courses'],
            null,
            [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            'Сервис временно недоступен.'
        );
        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (isset($result['message'])) {
            throw new BillingException($result['message']);
        }
        return $this->serializer->deserialize($response, 'array', 'json');
    }

    /**
     * @throws BillingUnavailableException
     * @throws BillingException
     * @throws JsonException
     */
    public function course(string $code)
    {
        $response = (new ApiClient())->get(
            $this->routes['courses'] . "/$code",
            null,
            [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            'Сервис временно недоступен.'
        );
        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (isset($result['message'])) {
            throw new BillingException($result['message']);
        }
        return $this->serializer->deserialize($response, 'array', 'json');
    }

    /**
     * @throws BillingUnavailableException
     * @throws BillingException
     * @throws JsonException
     */
    public function history(array $filters, string $bearerToken): array
    {
        $response = (new ApiClient())->get(
            $this->routes['transactions'],
            $filters,
            [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $bearerToken,
            ],
            'Сервис временно недоступен.'
        );
        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (isset($result['message'])) {
            throw new BillingException($result['message']);
        }
        return $this->serializer->deserialize($response, 'array', 'json');
    }

    /**
     * @throws BillingException
     * @throws BillingUnavailableException
     * @throws JsonException
     */
    public function pay(string $code, string $bearerToken)
    {
        $response = (new ApiClient())->post(
            $this->routes['courses'] . "/$code/pay",
            null,
            [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $bearerToken,
            ],
            'Сервис временно недоступен.'
        );

        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        // выбрасываем исключение о том, что пользователь ввел неверные данные для авторизации
        if (isset($result['message'])) {
            throw new BillingException($result['message']);
        }
        return $this->serializer->deserialize($response, 'array', 'json');
    }

    /**
     * @throws BillingUnavailableException
     * @throws BillingException
     * @throws JsonException
     */
    public function newCourse(
        string $courseData,
        string $bearerToken
    ): array {
        $response = (new ApiClient())->post(
            $this->routes['courses'],
            $courseData,
            [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $bearerToken,
            ],
            'Сервис временно недоступен.'
        );

        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (isset($result['message'])) {
            throw new BillingException($result['message']);
        }

        return $this->serializer->deserialize($response, 'array', 'json');
    }

    /**
     * @throws BillingUnavailableException
     * @throws BillingException
     * @throws JsonException
     */
    public function editCourse(
        string $oldCourseCode,
        string $courseData,
        string $bearerToken
    ): array {
        $response = (new ApiClient())->post(
            $this->routes['courses'] . "/$oldCourseCode/edit",
            $courseData,
            [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $bearerToken,
            ],
            'Сервис временно недоступен.'
        );

        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (isset($result['message'])) {
            throw new BillingException($result['message']);
        }

        return $this->serializer->deserialize($response, 'array', 'json');
    }
}
