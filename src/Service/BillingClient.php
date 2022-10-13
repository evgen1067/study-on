<?php

namespace App\Service;

use App\Dto\Response\UserAuthDto;
use App\Exception\BillingUnavailableException;
use App\Security\User;
use JMS\Serializer\SerializerInterface;
use JsonException;

class BillingClient
{
    private string $billingUrl;

    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->billingUrl = $_ENV['BILLING_URL'];
        $this->serializer = $serializer;
    }

    /**
     * @throws BillingUnavailableException
     * @throws JsonException
     */
    public function auth($credentials): User
    {
        $query = curl_init($this->billingUrl.'/api/v1/auth');
        $options = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $credentials,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: '.strlen($credentials),
            ],
        ];
        curl_setopt_array($query, $options);
        $response = curl_exec($query);

        // выбрасываем исключение, что сервис недоступен
        if (false === $response) {
            throw new BillingUnavailableException('Сервис временно недоступен.');
        }
        curl_close($query);

        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        // выбрасываем исключение о том, что пользователь ввел неверные даннеы для авторизации
        if (isset($result['code']) && 401 === $result['code']) {
            throw new BillingUnavailableException('Ошибка авторизации. Проверьте правильность введенных данных!');
        }

        $userDto = $this->serializer->deserialize($response, UserAuthDto::class, 'json');

        return User::fromDto($userDto);
    }
}
