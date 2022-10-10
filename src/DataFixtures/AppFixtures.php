<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $phpCourse = new Course();
        $phpCourse
            ->setCode('PHP-1')
            ->setName('Ключевые аспекты веб-разработки на PHP ')
            ->setDescription('Этот обзорный курс затрагивает основные аспекты современной веб-разработки в'.
                ' экосистеме PHP и позволяет понять контекст перед тем, как приступать к более глубокому изучению в '.
                'следующих курсах профессии. Мы рассмотрим понятия, с которыми сталкивается на практике любой '.
                'веб-разработчик: MVC, HTTP, ORM, фреймворки, шаблонизация, тесты и многое другое. Цель курса — не'.
                ' научить всем этим пользоваться, а дать общее представление и задать вектор дальнейшего обучения.'.
                ' К каждому уроку прилагается список тем и терминов, которые нужно изучить для полного понимания'.
                'описанной темы. Многие из них изучаются в последующих курсах.');

        $lesson = new Lesson();
        $lesson
            ->setName('Введение')
            ->setContent('Познакомиться с курсом.')
            ->setNumber(1);
        $phpCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Веб внутри PHP')
            ->setContent('Познакомиться с ключевым отличием PHP от других языков программирования.'.
                'Попробовать запустить свой первый сайт.')
            ->setNumber(2);
        $phpCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('HTTP')
            ->setContent('Познакомиться с основами сетевых протоколов.')
            ->setNumber(3);
        $phpCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Микрофреймворки')
            ->setContent('Рассмотреть идею микрофреймворков.')
            ->setNumber(4);
        $phpCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Шаблонизация')
            ->setContent('Узнать как формируется html на сервере.')
            ->setNumber(5);
        $phpCourse->addLesson($lesson);

        $manager->persist($phpCourse);

        $jsCourse = new Course();
        $jsCourse
            ->setCode('JS-1')
            ->setName('Основы JavaScript')
            ->setDescription('В курсе рассматриваются основы языка JavaScript, а также необходимые понятия '.
                'для программирования на нём. Такие как работа с ошибками, отладка, импорт модулей.');

        $lesson = new Lesson();
        $lesson
            ->setName('Введение')
            ->setContent('Познакомиться с курсом.')
            ->setNumber(1);
        $jsCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Hello, World!')
            ->setContent('Написать первую программу.')
            ->setNumber(2);
        $jsCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Инструкции')
            ->setContent('Изучить азы построения программ на JavaScript.')
            ->setNumber(3);
        $jsCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Арифметические операции')
            ->setContent('Переведём арифметические действия на язык программирования.')
            ->setNumber(4);
        $jsCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Ошибки оформления (синтаксиса и линтера)')
            ->setContent('Изучить виды ошибок и способы их решения.')
            ->setNumber(5);
        $jsCourse->addLesson($lesson);

        $manager->persist($jsCourse);

        $htmlCourse = new Course();
        $htmlCourse
            ->setCode('HTML-1')
            ->setName('Основы современной верстки')
            ->setDescription('При разработке современных интерфейсов учитываются не только последние'.
                ' технологии, но и мировые стандарты, предъявляемые к этим интерфейсам. Чтобы лучше понимать причины'.
                ' и следствия их появления, правильно применять в своих проектах, мы познакомимся с профессиональной'.
                ' терминологией и базовыми концепциями языков разметки и стилей HTML и CSS.');

        $lesson = new Lesson();
        $lesson
            ->setName('Введение')
            ->setContent('В этом уроке мы кратко расскажем о том, что узнаем на курсе и как эти'.
                ' знания можно применять на практике.')
            ->setNumber(1);
        $htmlCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Введение в HTML')
            ->setContent('Урок посвящен HTML верстке с нуля. Говорим о роли атрибутов и'.
                ' изучаем общую схему описания HTML тегов.')
            ->setNumber(2);
        $htmlCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Блочная модель')
            ->setContent('Какие элементы отвечают за каркас страницы, а какие помогают в процессе ее'.
                ' стилизации или добавления функциональных частей? Знакомимся с блочными и строчными элементами HTML'.
                ' и изучаем влияние стилей на итоговую ширину элементов.')
            ->setNumber(3);
        $htmlCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Семантический HTML')
            ->setContent('Основная цель любой HTML-верстки — передача смысла блоков. В этом уроке мы'.
                ' рассмотрим возможности последнего стандарта HTML5 в области семантики и узнаем о доступности в веб.')
            ->setNumber(4);
        $htmlCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Базовая структура HTML документа')
            ->setContent('Любой HTML-документ имеет базовую структуру, состоящую из тегов и служебных'.
                ' элементов. Они нужны браузеру для корректного отображения информации. В данном уроке разберемся с'.
                ' каждой строчкой этой структуры.')
            ->setNumber(5);
        $htmlCourse->addLesson($lesson);

        $manager->persist($htmlCourse);

        $gitCourse = new Course();
        $gitCourse
            ->setCode('GIT-1')
            ->setName('Введение в Git')
            ->setDescription('Git(система контроля версий) — один из главных инструментов в арсенале любого'.
                ' разработчика. Независимо от выбранного направления разработки, все программисты работают с исходным'.
                ' кодом проектов, который постоянно добавляется, изменяется и удаляется. В этом бесплатном курсе'.
                ' Git для начинающих вы научитесь правильному управлению этим процессом: как легко восстанавливаться'.
                ' после ошибок, изучать историю изменений и вести совместную разработку.');

        $lesson = new Lesson();
        $lesson
            ->setName('Введение')
            ->setContent('Знакомимся с курсом и говорим о проблемах, которые поджидают разработчика при'.
                ' работе с исходным кодом. Отвечаем на вопрос, почему Git стал универсальным инструментом,'.
                ' с которого начинается практически любой проект в разработке.')
            ->setNumber(1);
        $gitCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Установка и настройка')
            ->setContent('Рассказываем, как настроить операционную систему (Ubuntu/MacOS/Windows), установить'.
                ' Git и редактор кода VSCode, создать аккаунт на Github. А также о том,'.
                ' что поможет научиться владеть Git виртуозно.')
            ->setNumber(2);
        $gitCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Рабочий процесс')
            ->setContent('Подробно разбираем процесс от начала работы до фиксации результата в Git: как'.
                ' создать репозиторий, добавить в него файл и сделать коммит.')
            ->setNumber(3);
        $gitCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Интеграция с Github')
            ->setContent('Учимся настраивать GitHub, создавать в нем репозиторий и соединять его с локальным'.
                ' репозиторием. А также клонировать репозиторий, созданный на GitHub, на свой компьютер.')
            ->setNumber(4);
        $gitCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Рабочая директория (Working Directory)')
            ->setContent('Разбираемся с тем, что такое рабочая директория и чем она отличается от'.
                ' репозитория, учимся восстанавливать файлы.')
            ->setNumber(5);
        $gitCourse->addLesson($lesson);

        $manager->persist($gitCourse);

        $osCourse = new Course();
        $osCourse
            ->setCode('OS-1')
            ->setName('Операционные системы')
            ->setDescription('Курс посвящен главным принципам, лежащим в основе дизайна'.
                ' операционных систем. Мы узнаем о том, как и почему появились операционные системы, с какими'.
                ' проблемами столкнулись инженеры, как они их решили и продолжают решать. Как системы используют'.
                ' ресурсы компьютера, что такое виртуальная память, треды и мультитрединг. Как бороться с дедлоками и'.
                ' сегментацией памяти, зачем нужны семафоры и как с одним процессором можно создать'.
                ' иллюзию многозадачности.');

        $lesson = new Lesson();
        $lesson
            ->setName('Что такое компьютер и операционная система')
            ->setContent('Познакомиться с базовыми идеями: компьютер, операционная система'.
                ' и важные составные части компьютера.')
            ->setNumber(1);
        $osCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Эволюция ОС. Фундаментальные проблемы. Слои абстракции.')
            ->setContent('Узнать о самых главных проблемах компьютеров и разработки операционных систем.')
            ->setNumber(2);
        $osCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Представление и обработка процессов. Структуры данных. Очереди.')
            ->setContent('Разобраться в способе представления задач в контексте ОС.')
            ->setNumber(3);
        $osCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Треды. SMP. Микроядро')
            ->setContent('Изучить потоки и понять разницу между потоками и процессами,'.
                ' категории параллельных машин и микроядро.')
            ->setNumber(4);
        $osCourse->addLesson($lesson);

        $lesson = new Lesson();
        $lesson
            ->setName('Взаимное исключение. Семафоры. Мониторы. Передача сообщений. Проблема чтения/записи.')
            ->setContent('Изучить проблемы, связанные с работой нескольких процессов на одной системе,'.
                ' а также пути решения этих проблем.')
            ->setNumber(5);
        $osCourse->addLesson($lesson);

        $manager->persist($osCourse);

        $manager->flush();
    }
}
