<?php

namespace App\Service;

use App\Dto\Response\UserCurrentDto;
use App\Dto\TokenDto;
use App\Dto\UserDto;
use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Security\User;
use JMS\Serializer\SerializerInterface;
use JsonException;

class BillingClient
{
    protected SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param $credentials
     *
     * @throws BillingUnavailableException
     * @throws JsonException
     * @throws BillingException
     */
    public function auth($credentials): User
    {
        $api = new ApiService(
            '/api/v1/auth',
            'POST',
            $credentials,
            null,
            [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            'Сервис временно недоступен.'
        );
        $response = $api->execute();

        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        // выбрасываем исключение о том, что пользователь ввел неверные данные для авторизации
        if (isset($result['code']) && 401 === $result['code']) {
            throw new BillingException('Ошибка авторизации. Проверьте правильность введенных данных!');
        }

        $userDto = $this->serializer->deserialize($response, TokenDto::class, 'json');

        return User::fromDto($userDto);
    }

    /**
     * @throws BillingUnavailableException
     * @throws JsonException
     * @throws BillingException
     */
    public function register($data): User
    {
        $api = new ApiService(
            '/api/v1/register',
            'POST',
            $data,
            null,
            [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            'Сервис временно недоступен.'
        );
        $response = $api->execute();

        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (isset($result['code'])) {
            if (403 === $result['code']) {
                throw new BillingException($result['error']);
            }
            throw new BillingUnavailableException('Сервис временно недоступен.');
        }

        $userDto = $this->serializer->deserialize($response, TokenDto::class, 'json');

        return User::fromDto($userDto);
    }

    /**
     * @param $token
     * @return UserDto
     * @throws BillingUnavailableException
     * @throws JsonException
     * @throws BillingException
     */
    public function currentUser($token): UserDto
    {
        $api = new ApiService(
            '/api/v1/users/current',
            'GET',
            null,
            null,
            [
                'Authorization: Bearer '.$token,
                'accept: application/json',
            ],
            'Сервис временно недоступен.'
        );
        $response = $api->execute();

        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (isset($result['message'])) {
            throw new BillingException(json_encode($result['message'], JSON_THROW_ON_ERROR));
        }

        return $this->serializer->deserialize($response, UserDto::class, 'json');
    }
}