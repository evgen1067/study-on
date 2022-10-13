<?php

namespace App\Service;

use App\Exception\BillingUnavailableException;

class ApiService
{
    private string $route;

    private array $options;

    private string $exceptionMessage;

    public function __construct(
        string $route,
        string $method,
        $postParams = null,
        $getParams = null,
        $headers = null,
        $exceptionMessage = 'Сервис авторизации недоступен'
    ) {
        $this->route = $_ENV['BILLING_URL'].$route;
        $this->options = [
            CURLOPT_RETURNTRANSFER => true,
        ];

        if ('POST' === $method) {
            $this->options[CURLOPT_POST] = true;

            if (null !== $postParams) {
                $this->options[CURLOPT_POSTFIELDS] = $postParams;
            }
        } elseif (null !== $getParams) {
            $this->route .= '?'.http_build_query($getParams);
        }

        if (null !== $headers) {
            $this->options[CURLOPT_HTTPHEADER] = $headers;
        } else {
            $this->options[CURLOPT_HTTPHEADER] = [
                'Content-Type: application/json',
            ];
        }

        $this->exceptionMessage = $exceptionMessage;
    }

    /**
     * @throws BillingUnavailableException
     */
    public function execute(): bool|string
    {
        $query = curl_init($this->route);
        curl_setopt_array($query, $this->options);
        $response = curl_exec($query);

        if (false === $response) {
            throw new BillingUnavailableException($this->exceptionMessage);
        }
        curl_close($query);

        return $response;
    }
}
