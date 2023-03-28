<?php

namespace App\Service;

use App\DTO\TokenResponseDTO;
use App\DTO\UserResponseDTO;
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
        'register' => '/api/v1/register',
        'profile' => '/api/v1/users/current',
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
                'accept: application/json',
            ],
            'Сервис временно недоступен.'
        );
        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (isset($result['message'])) {
            throw new BillingException($result['message']);
        }
        return $this->serializer->deserialize($response, UserResponseDTO::class, 'json');
    }
}
