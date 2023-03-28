<?php

namespace App\Tests\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Tests\AbstractTest;
use JMS\Serializer\SerializerInterface;
use joshtronic\LoremIpsum;

class LessonControllerTest extends AbstractTest
{
    private SerializerInterface $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = self::getContainer()->get(SerializerInterface::class);
    }

    public function testGetActionsResponseOk(): void
    {
        $crawler = $this->beforeTesting();

        $client = self::getClient();
        $lessons = self::getEntityManager()->getRepository(Lesson::class)->findAll();
        foreach ($lessons as $lesson) {
            // детальная страница
            $client->request('GET', '/lessons/' . $lesson->getId());
            $this->assertResponseOk();

            // страница редактирования
            $client->request('GET', '/lessons/' . $lesson->getId() . '/edit');
            $this->assertResponseOk();
        }
    }

    public function testPostActionsResponseOk(): void
    {
        $crawler = $this->beforeTesting();

        $client = self::getClient();
        $lessons = self::getEntityManager()->getRepository(Lesson::class)->findAll();
        foreach ($lessons as $lesson) {
            // страница редактирования
            $client->request('POST', '/lessons/' . $lesson->getId() . '/edit');
            $this->assertResponseOk();
        }
    }

    public function testLessonCreatingWithEmptyName(): void
    {
        $crawler = $this->beforeTesting();

        $client = self::getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // перешли на детальную страницу курса
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // перешли к созданию урока
        $link = $crawler->filter('.app_lesson_new')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $submitBtn = $crawler->selectButton('Сохранить');

        // заполнили форму и отправили с пустым именем
        $lessonCreatingForm = $submitBtn->form([
            'lesson[name]' => '      ',
            'lesson[content]' => 'Test content',
            'lesson[number]' => 100,
        ]);

        $client->submit($lessonCreatingForm);
        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Название не может быть пустым'
        );
    }

    public function testLessonCreatingWithEmptyContent(): void
    {
        $crawler = $this->beforeTesting();

        $client = self::getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // перешли на детальную страницу курса
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // перешли к созданию урока
        $link = $crawler->filter('.app_lesson_new')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $submitBtn = $crawler->selectButton('Сохранить');

        // заполнили форму и отправили с пустым содержанием урока
        $lessonCreatingForm = $submitBtn->form([
            'lesson[name]' => 'Test name',
            'lesson[content]' => '      ',
            'lesson[number]' => 100,
        ]);

        $client->submit($lessonCreatingForm);
        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Содержимое урока не может быть пустым'
        );
    }

    public function testLessonCreatingWithEmptyNumber(): void
    {
        $crawler = $this->beforeTesting();

        $client = self::getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // перешли на детальную страницу курса
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // перешли к созданию урока
        $link = $crawler->filter('.app_lesson_new')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $submitBtn = $crawler->selectButton('Сохранить');

        // заполнили форму и отправили с пустым порядковым номером
        $lessonCreatingForm = $submitBtn->form([
            'lesson[name]' => 'Test name',
            'lesson[content]' => 'Test content',
            'lesson[number]' => '      ',
        ]);

        $client->submit($lessonCreatingForm);
        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Порядковый номер урока не может быть пустым'
        );
    }

    public function testLessonCreatingWithTooLongName(): void
    {
        $crawler = $this->beforeTesting();

        $client = self::getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // перешли на детальную страницу курса
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // перешли к созданию урока
        $link = $crawler->filter('.app_lesson_new')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $loremIpsum = new LoremIpsum();

        $submitBtn = $crawler->selectButton('Сохранить');

        // заполнили форму и отправили с пустым названием урока
        $lessonCreatingForm = $submitBtn->form([
            'lesson[name]' => $loremIpsum->words(50),
            'lesson[content]' => 'Test content',
            'lesson[number]' => 100,
        ]);
        $client->submit($lessonCreatingForm);
        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Название урока должно быть не более 255 символов'
        );
    }

    public function testLessonCreatingWithMoreNumber(): void
    {
        $crawler = $this->beforeTesting();

        $client = self::getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // перешли на детальную страницу курса
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // перешли к созданию урока
        $link = $crawler->filter('.app_lesson_new')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $loremIpsum = new LoremIpsum();

        $submitBtn = $crawler->selectButton('Сохранить');

        // заполнили форму и отправили с слишком большим числом
        $lessonCreatingForm = $submitBtn->form([
            'lesson[name]' => 'Test name',
            'lesson[content]' => 'Test content',
            'lesson[number]' => 11111,
        ]);
        $client->submit($lessonCreatingForm);
        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Значение поля должно быть в пределах от 1 до 10000'
        );
    }

    public function testLessonCreatingWithNumberIsNotNumber(): void
    {
        $crawler = $this->beforeTesting();

        $client = self::getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // перешли на детальную страницу курса
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // перешли к созданию урока
        $link = $crawler->filter('.app_lesson_new')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $loremIpsum = new LoremIpsum();

        $submitBtn = $crawler->selectButton('Сохранить');

        // заполнили форму и отправили с номером, не являющимся числом
        $lessonCreatingForm = $submitBtn->form([
            'lesson[name]' => 'Test name',
            'lesson[content]' => 'Test content',
            'lesson[number]' => 'Test number',
        ]);
        $client->submit($lessonCreatingForm);
        self::assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Пожалуйста, введите номер.'
        );
    }

    public function testSuccessfulLessonCreating(): void
    {
        $crawler = $this->beforeTesting();

        $client = self::getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // перешли на детальную страницу курса
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // перешли к созданию урока
        $link = $crawler->filter('.app_lesson_new')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $submitBtn = $crawler->selectButton('Сохранить');

        // заполнили форму и отправили
        $lessonCreatingForm = $submitBtn->form([
            'lesson[name]' => 'Test name',
            'lesson[content]' => 'Test content',
            'lesson[number]' => 9999,
        ]);

        $course = self::getEntityManager()
            ->getRepository(Course::class)
            ->findOneBy(['id' => $lessonCreatingForm['lesson[course]']->getValue()]);

        $client->submit($lessonCreatingForm);

        // проверяем, что оказались на странице курса, который редактировали
        self::assertSame($client->getResponse()->headers->get('location'), '/courses/' . $course->getId());
        $crawler = $client->followRedirect();
        $this->assertResponseOk();

        // проверяем, что урок был отредактирован
        $this->assertSame($crawler->filter('.lesson')->last()->text(), '9999. Test name');

        // зайдем на его страницу
        $link = $crawler->filter('.lesson')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // проверим название и содержание
        $this->assertSame($crawler->filter('.lesson-name')->first()->text(), 'Test name');
        $this->assertSame($crawler->filter('.lesson-content')->first()->text(), 'Test content');
    }

    public function testSuccessfulLessonEditing(): void
    {
        $crawler = $this->beforeTesting();

        $client = self::getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // перешли на детальную страницу курса
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // перешли на детальную страницу урока
        $link = $crawler->filter('.lesson')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // перешли к редактированию урока
        $link = $crawler->filter('.app_lesson_edit')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $submitBtn = $crawler->selectButton('Обновить');
        $form = $submitBtn->form();

        // сохраняем редактируемый курс
        $course = self::getEntityManager()
            ->getRepository(Course::class)
            ->findOneBy(['id' => $form['lesson[course]']->getValue()]);

        // заполняем и отправляем форму
        $form['lesson[name]'] = 'Edit lesson name';
        $form['lesson[content]'] = 'Edit lesson content';
        $form['lesson[number]'] = 1;
        $client->submit($form);

        // проверяем, что оказались на странице курса, который редактировали
        self::assertSame($client->getResponse()->headers->get('location'), '/courses/' . $course->getId());
        $crawler = $client->followRedirect();
        $this->assertResponseOk();

        // проверяем, что урок был отредактирован
        $this->assertSame($crawler->filter('.lesson')->first()->text(), '1. Edit lesson name');

        // зайдем на его страницу
        $link = $crawler->filter('.lesson')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // проверим название и содержание
        $this->assertSame($crawler->filter('.lesson-name')->first()->text(), 'Edit lesson name');
        $this->assertSame($crawler->filter('.lesson-content')->first()->text(), 'Edit lesson content');
    }

    public function testLessonDeleting(): void
    {
        $crawler = $this->beforeTesting();

        $client = self::getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // перешли на детальную страницу курса
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // перешли на детальную страницу урока
        $link = $crawler->filter('.lesson')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // перешли к редактированию урока
        $link = $crawler->filter('.app_lesson_edit')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $submitBtn = $crawler->selectButton('Обновить');
        $form = $submitBtn->form();

        // сохраняем редактируемый курс
        $course = self::getEntityManager()
            ->getRepository(Course::class)
            ->findOneBy(['id' => $form['lesson[course]']->getValue()]);

        // число до удаления
        $countBeforeDeleting = count($course->getLessons());

        // перешли обратно к курсу
        $link = $crawler->filter('.app_course_show')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // перешли к уроку
        $link = $crawler->filter('.lesson')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // удалили урок и проверили редирект
        $client->submitForm('Удалить');
        self::assertSame($client->getResponse()->headers->get('location'), '/courses/' . $course->getId());
        $crawler = $client->followRedirect();

        // проверили, что кол-во уроков уменьшилось
        self::assertCount($countBeforeDeleting - 1, $crawler->filter('.lesson'));
    }

    private function beforeTesting()
    {
        $auth = new AuthTest();
        $auth->setSerializer($this->serializer);
        return $auth->auth();
    }

    protected function getFixtures(): array
    {
        return [AppFixtures::class];
    }
}
