<?php

namespace App\Service;

use App\Exception\BillingUnavailableException;

class ApiClient
{
    /**
     * @throws BillingUnavailableException
     */
    public function get(
        string $route,
        $getParams = null,
        $headers = null,
        $exceptionMessage = 'Сервис биллинга временно недоступен.'
    ): bool|string {
        $route = $_ENV['BILLING_URL'] . $route . ((null !== $getParams) ? '?' . http_build_query($getParams) : '');
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ((null !== $headers) ? $headers : ['Content-Type: application/json'])
        ];
        $query = curl_init($route);
        curl_setopt_array($query, $options);
        $response = curl_exec($query);

        if (false === $response) {
            throw new BillingUnavailableException($exceptionMessage);
        }
        curl_close($query);
        return $response;
    }

    /**
     * @throws BillingUnavailableException
     */
    public function post(
        string $route,
        $postParams = null,
        $headers = null,
        $exceptionMessage = 'Сервис биллинга временно недоступен.'
    ): bool|string {
        $route = $_ENV['BILLING_URL'] . $route;
        $options = [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => (null !== $headers ? $headers : ['Content-Type: application/json']),
            CURLOPT_POSTFIELDS => (null !== $postParams ? $postParams : ''),
        ];
        $query = curl_init($route);
        curl_setopt_array($query, $options);
        $response = curl_exec($query);

        if (false === $response) {
            throw new BillingUnavailableException($exceptionMessage);
        }
        curl_close($query);
        return $response;
    }


}