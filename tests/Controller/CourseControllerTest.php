<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\Course;
use App\Tests\AbstractTest;
use joshtronic\LoremIpsum;

class CourseControllerTest extends AbstractTest
{
    /**
     * @dataProvider urlProviderIsSuccessful
     */
    public function testPageIsSuccessful($url): void
    {
        $client = self::getClient();
        $client->request('GET', $url);
        $this->assertResponseOk();
    }

    public function urlProviderIsSuccessful(): \Generator
    {
        yield ['/'];
        yield ['/courses/'];
        yield ['/courses/new'];
    }

    /**
     * @dataProvider urlProviderNotFound
     */
    public function testPageIsNotFound($url): void
    {
        $client = self::getClient();
        $client->request('GET', $url);
        $this->assertResponseNotFound();
    }

    public function urlProviderNotFound(): \Generator
    {
        yield ['/not-found/'];
        yield ['/courses/-1'];
    }

    public function testGetActionsResponseOk(): void
    {
        $client = self::getClient();
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        foreach ($courses as $course) {
            // детальная страница
            $client->request('GET', '/courses/'.$course->getId());
            $this->assertResponseOk();

            // страница редактирования
            $client->request('GET', '/courses/'.$course->getId().'/edit');
            $this->assertResponseOk();

            // страница создания урока
            $client->request('GET', '/courses/'.$course->getId().'/new/lesson');
            $this->assertResponseOk();
        }
    }

    public function testPostActionsResponseOk(): void
    {
        $client = self::getClient();
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        foreach ($courses as $course) {
            // детальная страница
            $client->request('POST', '/courses/'.$course->getId().'/edit');
            $this->assertResponseOk();

            // страница добавления урока
            $client->request('POST', '/courses/'.$course->getId().'/new/lesson');
            $this->assertResponseOk();
        }
    }

    public function testNumberOfCourses(): void
    {
        $client = self::getClient();
        $crawler = $client->request('GET', '/courses/');
        $coursesCount = count(self::getEntityManager()->getRepository(Course::class)->findAll());
        self::assertCount($coursesCount, $crawler->filter('.course'));
    }

    public function testNumberOfCourseLessons(): void
    {
        $client = self::getClient();
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        foreach ($courses as $course) {
            $crawler = $client->request('GET', '/courses/'.$course->getId());
            $lessonsCount = count($course->getLessons());
            self::assertCount($lessonsCount, $crawler->filter('.list-group-item'));
        }
    }

    public function testSuccessfulCourseCreating(): void
    {
        // на странице списка курсов
        $client = self::getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // кликнули на ссылку для перехода к созданию курса
        $link = $crawler->filter('.app_course_new')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // заполнили форму и отправили
        $submitBtn = $crawler->selectButton('Сохранить');
        $courseCreatingForm = $submitBtn->form([
            'course[code]' => 'CODE-1',
            'course[name]' => 'Test course name',
            'course[description]' => 'Test course description',
        ]);
        $client->submit($courseCreatingForm);

        // нашли этот курс в БД и проверили, что редирект на его страницу
        $course = self::getEntityManager()->getRepository(Course::class)->findOneBy([
            'code' => 'CODE-1',
        ]);
        self::assertSame($client->getResponse()->headers->get('location'), '/courses/'.$course->getId());
        $crawler = $client->followRedirect();
        $this->assertResponseOk();

        // проверяем, что данные переданные верно отображаются на странице
        $this->assertSame($crawler->filter('.course-name')->text(), $course->getName());
        $this->assertSame($crawler->filter('.course-description')->text(), $course->getDescription());
    }

    public function testCourseCreatingWithEmptyCode(): void
    {
        // на странице списка курсов
        $client = self::getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // кликнули на ссылку для перехода к созданию курса
        $link = $crawler->filter('.app_course_new')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $submitBtn = $crawler->selectButton('Сохранить');

        // заполнили форму и отправили с пустым кодом
        $courseCreatingForm = $submitBtn->form([
            'course[code]' => '      ',
            'course[name]' => 'Test name',
            'course[description]' => 'Test description',
        ]);
        $client->submit($courseCreatingForm);
        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Символьный код не может быть пустым'
        );
    }

    public function testCourseCreatingWithEmptyName(): void
    {
        // на странице списка курсов
        $client = self::getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // кликнули на ссылку для перехода к созданию курса
        $link = $crawler->filter('.app_course_new')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $submitBtn = $crawler->selectButton('Сохранить');

        // заполнили форму и отправили с пустым названием
        $courseCreatingForm = $submitBtn->form([
            'course[code]' => 'PHP-TEST',
            'course[name]' => '      ',
            'course[description]' => 'Test description',
        ]);
        $client->submit($courseCreatingForm);
        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Название не может быть пустым'
        );
    }

    public function testCourseCreatingWithNotUniqueCode(): void
    {
        // на странице списка курсов
        $client = self::getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // кликнули на ссылку для перехода к созданию курса
        $link = $crawler->filter('.app_course_new')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // заполнили форму и отправили с не уникальным кодом
        $submitBtn = $crawler->selectButton('Сохранить');
        $courseCreatingForm = $submitBtn->form([
            'course[code]' => 'PHP-1',
            'course[name]' => 'Test name',
            'course[description]' => 'Test description',
        ]);
        $client->submit($courseCreatingForm);
        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Данный код уже зарегистрирован в системе!'
        );
    }

    public function testCourseCreatingWithTooLongName(): void
    {
        // на странице списка курсов
        $client = self::getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // кликнули на ссылку для перехода к созданию курса
        $link = $crawler->filter('.app_course_new')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $submitBtn = $crawler->selectButton('Сохранить');

        $loremIpsum = new LoremIpsum();

        // заполнили форму и отправили с длиной названия более 255 символов
        $courseCreatingForm = $submitBtn->form([
            'course[code]' => 'TEST',
            'course[name]' => $loremIpsum->words(50),
            'course[description]' => 'Test description',
        ]);
        $client->submit($courseCreatingForm);
        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Название должно быть не более 255 символов'
        );
    }

    public function testCourseCreatingWithTooLongDescription(): void
    {
        // на странице списка курсов
        $client = self::getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // кликнули на ссылку для перехода к созданию курса
        $link = $crawler->filter('.app_course_new')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $submitBtn = $crawler->selectButton('Сохранить');

        $loremIpsum = new LoremIpsum();

        // заполнили форму и отправили с длиной описания более 1000 символов
        $courseCreatingForm = $submitBtn->form([
            'course[code]' => 'TEST',
            'course[name]' => 'Test name',
            'course[description]' => $loremIpsum->words(200),
        ]);
        $client->submit($courseCreatingForm);
        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Описание должно быть не более 1000 символов'
        );
    }

    public function testCourseEditing(): void
    {
        // на странице списка курсов
        $client = self::getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // на детальной странице курса
        $link = $crawler->filter('.app_course_edit')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $submitButton = $crawler->selectButton('Обновить');
        $form = $submitButton->form();
        // сохраняем id редактируемого курса
        $courseId = self::getEntityManager()
            ->getRepository(Course::class)
            ->findOneBy(['code' => $form['course[code]']->getValue()])->getId();
        $form['course[code]'] = 'EDIT-COURSE';
        $form['course[name]'] = 'Edit name course';
        $form['course[description]'] = 'Edit description course';
        $client->submit($form);
        // проверяем, что оказались на странице курса, который редактировали
        self::assertSame($client->getResponse()->headers->get('location'), '/courses/'.$courseId);
        $crawler = $client->followRedirect();
        $this->assertResponseOk();
        // проверяем, что данные изменились
        $this->assertSame($crawler->filter('.course-name')->text(), 'Edit name course');
        $this->assertSame($crawler->filter('.course-description')->text(), 'Edit description course');
    }

    public function testCourseDeleting(): void
    {
        // на странице списка курсов
        $client = self::getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        $coursesCount = count(self::getEntityManager()->getRepository(Course::class)->findAll());

        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $client->submitForm('Удалить');
        self::assertSame($client->getResponse()->headers->get('location'), '/courses/');
        $crawler = $client->followRedirect();

        $coursesCountAfterDelete = count(self::getEntityManager()->getRepository(Course::class)->findAll());
        // проверка, что кол-во курсов было уменьшено в БД и на странице соответственно
        self::assertSame($coursesCount - 1, $coursesCountAfterDelete);
        self::assertCount($coursesCountAfterDelete, $crawler->filter('.course'));
    }

    protected function getFixtures(): array
    {
        return [AppFixtures::class];
    }
}