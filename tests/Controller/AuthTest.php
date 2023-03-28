<?php

namespace App\Tests\Controller;

use App\Service\BillingClient;
use App\Tests\AbstractTest;
use App\Tests\Mock\BillingClientMock;
use JMS\Serializer\SerializerInterface;

class AuthTest extends AbstractTest
{
    private SerializerInterface $serializer;

    private array $validCredentials = [
        'username' => 'admin@study-on.ru',
        'password' => 'password',
    ];

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    public function auth()
    {
        $client = $this->billingClient();
        $crawler = $client->request('GET', '/');
        $this->assertResponseOk();

        $link = $crawler->selectLink('Авторизация')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $submitBtn = $crawler->selectButton('Войти');
        $login = $submitBtn->form([
            'email' => $this->validCredentials['username'],
            'password' => $this->validCredentials['password'],
        ]);
        $client->submit($login);

        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();
        self::assertEquals('/courses/', $client->getRequest()->getPathInfo());
        return $crawler;
    }

    private function billingClient()
    {
        self::getClient()->disableReboot();

        self::getClient()->getContainer()->set(
            BillingClient::class,
            new BillingClientMock($this->serializer)
        );

        return self::getClient();
    }

}