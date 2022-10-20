<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Form\CourseType;
use App\Form\LessonType;
use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use App\Security\User;
use App\Service\BillingClient;
use Exception;
use JMS\Serializer\Serializer;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/courses')]
class CourseController extends AbstractController
{
    /**
     * @param CourseRepository $courseRepository
     * @param BillingClient $billingClient
     * @return Response
     * @throws JsonException
     * @throws Exception
     */
    #[Route('/', name: 'app_course_index', methods: ['GET'])]
    public function index(CourseRepository $courseRepository, BillingClient $billingClient): Response
    {
        try {
            # перевели курсы в массивы
            $coursesFromBilling = $this->mapToKey($billingClient->getCourses(), 'code');
            $coursesFromLocal = $this->mapToKey($courseRepository->findAllInArray(), 'code');

            # для неавторизованного выводим только те курсы, которых нет в БД биллинга или те, которые бесплатны
            if (!$this->getUser()) {
                $resultCourses = [];
                foreach ($coursesFromLocal as $code => $course) {
                    if (!isset($coursesFromBilling[$code]) || $coursesFromBilling[$code]['type'] === 'free') {
                        $resultCourses[] = [
                            'course' => $course,
                            'billingInfo' => ['type' => 'free'],
                            'transaction' => null
                        ];
                    }
                }
                $this->addFlash('success', 'Курсы успешно загружены!');
                return $this->render('course/index.html.twig', [
                    'courses' => $resultCourses,
                ]);
            }
            /**
             * @var User $user
             */
            $user = $this->getUser();

            # получаем список покупок текущего пользователя и скипаем те, что уже истекли
            $transactions = $billingClient->getTransactions([
                'type' => 'payment',
                'skip_expired' => true
            ], $user->getApiToken());
            $transactions = $this->mapToKey($transactions, 'course_code');

            $resultCourses = [];
            foreach ($coursesFromLocal as $code => $course) {
                $resultCourses[] = [
                    'course' => $course,
                    // если нет в БД биллинга, значит бесплатный
                    'billingInfo' => $coursesFromBilling[$code] ?? ['type' => 'free'],
                    // оставляем пустым если нет транзакций по этому курсу
                    'transaction' => $transactions[$code] ?? null
                ];
            }
            $this->addFlash('success', 'Курсы успешно загружены!');
            return $this->render('course/index.html.twig', [
                'courses' => $resultCourses,
            ]);

        } catch (BillingException|BillingUnavailableException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->render('course/index.html.twig', [
                'courses' => [],
            ]);
        }
    }

    /**
     * @throws Exception
     */
    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    public function new(
        Request          $request,
        CourseRepository $courseRepository,
        BillingClient    $billingClient): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);
        /**
         * @var User $user
         */
        $user = $this->getUser();
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = [
                    'type' => $form->get('type')->getData(),
                    'title' => $form->get('name')->getData(),
                    'code' => $form->get('code')->getData(),
                    'price' => $form->get('price')->getData()
                ];
                $data = json_encode($data, JSON_THROW_ON_ERROR);
                $responseFromBilling = $billingClient->newCourse(
                    $data,
                    $user->getApiToken()
                );

                $courseRepository->save($course, true);

                $this->addFlash('success', 'Курс успешно создан!');

                return $this->redirectToRoute('app_course_show', [
                    'id' => $course->getId()
                ], Response::HTTP_SEE_OTHER);
            } catch (BillingException|BillingUnavailableException|JsonException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }
        return $this->renderForm('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/pay', name: 'app_course_pay', methods: ['GET'])]
    public function pay(Course $course, BillingClient $billingClient): RedirectResponse
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();
        if(!$user) {
            $this->addFlash(
                'warning',
                'Зарегистрируйтесь или авторизуйтесь, чтобы получить доступ к этому курсу!'
            );
            return $this->redirectToRoute('app_login');
        }

        try {
            $billingClient->pay($course->getCode(), $user->getApiToken());
            $this->addFlash(
                'success',
                'Курс успешно приобретен!'
            );
        } catch (BillingException|JsonException|BillingUnavailableException $e) {
            $this->addFlash(
                'error',
                $e->getMessage()
            );
        }
        return $this->redirectToRoute('app_course_index');
    }

    /**
     * @param Course $course
     * @param BillingClient $billingClient
     * @return Response
     * @throws JsonException
     * @throws Exception
     */
    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Course $course, BillingClient $billingClient): Response
    {
        $billingCourse = null;
        try {
            $billingCourse = $billingClient->getCourse($course->getCode());

            if (is_null($billingCourse) || $billingCourse['type'] === 'free') {
                return $this->render('course/show.html.twig', [
                    'course' => $course,
                ]);
            }

            if (!$this->getUser()) {
                $this->addFlash(
                    'warning',
                    'Зарегистрируйтесь или авторизуйтесь, чтобы получить доступ к этому курсу!'
                );
                return $this->redirectToRoute('app_login');
            }

            /**
             * @var User $user
             */
            $user = $this->getUser();

            # получаем список транзакций пользователя по этому курсу, которые еще действительны
            $transaction = $billingClient->getTransactions([
                'type' => 'payment',
                'course_code' => $course->getCode(),
                'skip_expired' => true
            ], $user->getApiToken());

            // если пользователь приобрел данный курс или если это АДМИН разрешаем остаться на странице
            if ($transaction || $this->isGranted('ROLE_SUPER_ADMIN')) {
                return $this->render('course/show.html.twig', [
                    'course' => $course,
                ]);
            }
            throw new AccessDeniedException('Данный курс вам недоступен!');
        } catch (BillingException|BillingUnavailableException $e) {
            $this->addFlash('error', $e->getMessage());
            if (is_null($billingCourse) || $billingCourse['type'] === 'free') {
                return $this->render('course/show.html.twig', [
                    'course' => $course,
                ]);
            }

            return $this->redirectToRoute('app_course_index');
        } catch (AccessDeniedException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_course_index');
        }
    }

    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Course $course,
        CourseRepository $courseRepository,
        BillingClient $billingClient
    ): Response
    {
        $oldCourseCode = $courseRepository->findOneBy(['id' => $course->getId()])->getCode();
        if (!$this->getUser()) {
            $this->addFlash(
                'warning',
                'Зарегистрируйтесь или авторизуйтесь, чтобы получить доступ к этой возможности!'
            );
            return $this->redirectToRoute('app_login');
        }

        try {
            $billingCourse = $billingClient->getCourse($oldCourseCode);
        } catch (BillingException|JsonException|BillingUnavailableException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_course_show', ['id' => $oldCourseCode]);
        }

        /**
         * @var User $user
         */
        $user = $this->getUser();

        $form = $this->createForm(
            CourseType::class,
            $course, [
            'type' => $billingCourse['type'],
            'price' => (float)$billingCourse['price']
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $billingCourse = $billingClient->getCourse($oldCourseCode);

                $data = [
                    'type' => $form->get('type')->getData(),
                    'title' => $form->get('name')->getData(),
                    'code' => $form->get('code')->getData(),
                    'price' => $form->get('price')->getData()
                ];
                $data = json_encode($data, JSON_THROW_ON_ERROR);
                $responseFromBilling = $billingClient->editCourse(
                    $oldCourseCode,
                    $data,
                    $user->getApiToken()
                );

                $courseRepository->save($course, true);

                $this->addFlash('success', 'Курс успешно изменен!');

                return $this->redirectToRoute(
                    'app_course_show',
                    [
                        'id' => $course->getId()
                    ],
                    Response::HTTP_SEE_OTHER);
            } catch (BillingException|BillingUnavailableException|JsonException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->renderForm('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_delete', methods: ['POST'])]
    public function delete(Request $request, Course $course, CourseRepository $courseRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $course->getId(), $request->request->get('_token'))) {
            $courseRepository->remove($course, true);
        }

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{course}/lessons/new', name: 'app_lesson_new', methods: ['GET', 'POST'])]
    public function newLesson(Course $course, Request $request, LessonRepository $lessonRepository): Response
    {
        $lesson = new Lesson();
        $lesson->setCourse($course);
        $form = $this->createForm(LessonType::class, $lesson, [
            'course' => $course,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lessonRepository->save($lesson, true);

            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('lesson/new.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
            'course' => $course,
        ]);
    }

    private function mapToKey($array, $key): array
    {
        $result = [];
        foreach ($array as $obj) {
            $result[$obj[$key]] = $obj;
        }
        return $result;
    }
}
