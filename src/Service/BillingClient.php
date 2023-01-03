<?php

namespace App\Service;

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
                throw new BillingException($result['message']);
            }

            throw new BillingUnavailableException('Сервис временно недоступен.');
        }

        $userDto = $this->serializer->deserialize($response, TokenDto::class, 'json');

        return User::fromDto($userDto);
    }

    /**
     * @param $token
     *
     * @throws BillingException
     * @throws BillingUnavailableException
     * @throws JsonException
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
            throw new BillingException($result['message']);
        }

        return $this->serializer->deserialize($response, UserDto::class, 'json');
    }

    /**
     * @throws BillingUnavailableException
     * @throws BillingException
     * @throws JsonException
     */
    public function refreshToken($data)
    {
        $api = new ApiService(
            '/api/v1/token/refresh',
            'POST',
            $data,
            null,
            [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            'Сервис биллинга недоступен.'
        );
        $response = $api->execute();

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
    public function getCourses(): mixed
    {
        $api = new ApiService(
            '/api/v1/courses',
            'GET',
            null,
            null,
            [
                'accept: application/json',
            ],
            'Сервис временно недоступен.'
        );
        $response = $api->execute();
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
    public function getCourse(string $courseCode): mixed
    {
        $api = new ApiService(
            '/api/v1/courses/'.$courseCode,
            'GET',
            null,
            null,
            [
                'accept: application/json',
            ],
            'Сервис биллинга недоступен.'
        );
        $response = $api->execute();

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
    public function getTransactions($filters, $token)
    {
        $api = new ApiService(
            '/api/v1/transactions',
            'GET',
            null,
            $filters,
            [
                'accept: application/json',
                'Authorization: Bearer '.$token,
            ],
            'Сервис биллинга недоступен.'
        );
        $response = $api->execute();
        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (isset($result['message'])) {
            throw new BillingException($result['message']);
        }

        return $this->serializer->deserialize($response, 'array', 'json');
    }

    /**
     * @param $courseCode
     * @param $token
     *
     * @throws BillingException
     * @throws BillingUnavailableException
     * @throws JsonException
     */
    public function pay($courseCode, $token)
    {
        $api = new ApiService(
            '/api/v1/courses/'.$courseCode.'/pay',
            'POST',
            null,
            null,
            [
                'accept: application/json',
                'Authorization: Bearer '.$token,
            ],
            'Сервис биллинга недоступен.'
        );
        $response = $api->execute();

        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (isset($result['message'])) {
            throw new BillingException($result['message']);
        }

        return $this->serializer->deserialize($response, 'array', 'json');
    }

    /**
     * @param $courseData
     * @param $token
     *
     * @throws BillingException
     * @throws BillingUnavailableException
     * @throws JsonException
     */
    public function newCourse($courseData, $token)
    {
        $api = new ApiService(
            '/api/v1/courses/new',
            'POST',
            $courseData,
            null,
            [
                'accept: application/json',
                'Authorization: Bearer '.$token,
            ],
            'Сервис биллинга недоступен.'
        );
        $response = $api->execute();

        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (isset($result['message'])) {
            throw new BillingException($result['message']);
        }

        return $this->serializer->deserialize($response, 'array', 'json');
    }

    /**
     * @param $oldCourseCode
     * @param $courseData
     * @param $token
     *
     * @throws BillingException
     * @throws BillingUnavailableException
     * @throws JsonException
     */
    public function editCourse($oldCourseCode, $courseData, $token)
    {
        $api = new ApiService(
            '/api/v1/courses/'.$oldCourseCode.'/edit',
            'POST',
            $courseData,
            null,
            [
                'accept: application/json',
                'Authorization: Bearer '.$token,
            ],
            'Сервис биллинга недоступен.'
        );
        $response = $api->execute();

        $result = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (isset($result['message'])) {
            throw new BillingException($result['message']);
        }

        return $this->serializer->deserialize($response, 'array', 'json');
    }
}
