<?php

namespace App\Tests\Controller;

use App\Service\BillingClient;
use App\Tests\AbstractTest;
use App\Tests\Mock\BillingClientMock;
use JMS\Serializer\SerializerInterface;

class ProfileControllerTest extends AbstractTest
{
    private SerializerInterface $serializer;

    private array $validCredentials = [
        'username' => 'admin@study-on.ru',
        'password' => 'password',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::getContainer()->get(SerializerInterface::class);
    }

    public function testProfileAndHistory(): void
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

        $link = $crawler->selectLink('Профиль')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        self::assertEquals('/profile', $client->getRequest()->getPathInfo());

        self::assertSelectorTextContains('td.text-center.email', 'admin@study-on.ru');
        self::assertSelectorTextContains('td.text-center.role', 'Администратор');
        self::assertSelectorTextContains('td.text-center.balance', '7000');

        $link = $crawler->selectLink('История транзакций')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        self::assertEquals('/profile/history', $client->getRequest()->getPathInfo());

        self::assertCount(5, $crawler->filter('.transactions-value'));
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