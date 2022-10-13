<?php

namespace App\Service;

use App\Dto\Response\UserAuthDto;
use App\Dto\Response\UserCurrentDto;
use App\Exception\BillingUnavailableException;
use App\Security\User;
use JMS\Serializer\SerializerInterface;
use JsonException;

class BillingClient
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param $credentials
     *
     * @throws BillingUnavailableException
     * @throws JsonException
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

        // выбрасываем исключение о том, что пользователь ввел неверные даннеы для авторизации
        if (isset($result['code']) && 401 === $result['code']) {
            throw new BillingUnavailableException('Ошибка авторизации. Проверьте правильность введенных данных!');
        }

        $userDto = $this->serializer->deserialize($response, UserAuthDto::class, 'json');

        return User::fromDto($userDto);
    }

    /**
     * @param $token
     *
     * @throws BillingUnavailableException|JsonException
     *
     * @return mixed
     */
    public function currentUser($token): UserCurrentDto
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
            throw new BillingUnavailableException(json_encode($result['message'], JSON_THROW_ON_ERROR));
        }

        return $this->serializer->deserialize($response, UserCurrentDto::class, 'json');
    }
}
