<?php

namespace App\Tests\Controller;

use App\Service\BillingClient;
use App\Tests\AbstractTest;
use App\Tests\Mock\BillingClientMock;
use JMS\Serializer\SerializerInterface;

class SecurityControllerTest extends AbstractTest
{
    private SerializerInterface $serializer;

    private array $validCredentials = [
        'username' => 'user@study-on.ru',
        'password' => 'password',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::getContainer()->get(SerializerInterface::class);
    }

    public function testAuthAndLogout(): void
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

        $link = $crawler->selectLink('Выход')->link();
        $crawler = $client->click($link);

        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();

        $link = $crawler->selectLink('Авторизация')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $submitBtn = $crawler->selectButton('Войти');
        $login = $submitBtn->form([
            'email' => $this->validCredentials['username'],
            'password' => 'magic',
        ]);
        $client->submit($login);

        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();

        self::assertSelectorTextContains(
            '.alert.alert-danger',
            'Ошибка авторизации. Проверьте правильность введенных данных!'
        );
    }

    public function testRegisterAndLogout(): void
    {
        $client = $this->billingClient();
        $crawler = $client->request('GET', '/');
        $this->assertResponseOk();

        $link = $crawler->selectLink('Регистрация')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $login = $crawler->selectButton('Сохранить')->form([
            'register[username]' => 'test@study-on.ru',
            'register[password][first]' => 'password',
            'register[password][second]' => 'password',
        ]);
        $client->submit($login);

        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();

        self::assertEquals('/courses/', $client->getRequest()->getPathInfo());

        $link = $crawler->selectLink('Выход')->link();
        $crawler = $client->click($link);

        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();

        $link = $crawler->selectLink('Регистрация')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $login = $crawler->selectButton('Сохранить')->form([
            'register[username]' => $this->validCredentials['username'],
            'register[password][first]' => 'password',
            'register[password][second]' => 'password',
        ]);
        $client->submit($login);

        $this->assertResponseOk();

        self::assertEquals('/register', $client->getRequest()->getPathInfo());

        self::assertSelectorTextContains(
            '.notification.symfony-notification.error',
            'Email уже используется.'
        );
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