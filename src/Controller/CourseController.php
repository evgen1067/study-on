<?php

namespace App\Controller;

use App\DTO\Request\CourseRequestDTO;
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
use App\Utils\Utils;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/courses')]
class CourseController extends AbstractController
{
    #[Route('/', name: 'app_course_index', methods: ['GET'])]
    public function index(
        CourseRepository $repo,
        BillingClient $bc
    ): Response {
        try {
            $billingCourses = Utils::mapToKey($bc->courses(), 'code');
            $dbCourses = Utils::mapToKey($repo->findAllArray(), 'code');

            if (!$this->getUser()) {
                $unauthorizedCourses = [];
                foreach ($dbCourses as $code => $dbCourse) {
                    if (!isset($billingCourses[$code]) || $billingCourses[$code]['type'] === 'free') {
                        $unauthorizedCourses[] = [
                            'course' => $dbCourse,
                            'billingInfo' => ['type' => 'free'],
                            'transaction' => null,
                        ];
                    }
                }
                $this->addFlash('success', 'Курсы успешно загружены!');
                return $this->render('course/index.html.twig', [
                    'courses' => $unauthorizedCourses,
                ]);
            }
            /**
             * @var User $user
             */
            $user = $this->getUser();
            $trans = Utils::mapToKey($bc->history([
                'type' => 'payment',
                'skip_expired' => true,
            ], $user->getApiToken()), 'course_code');

            $authorizedCourses = [];
            foreach ($dbCourses as $code => $dbCourse) {
                $authorizedCourses[] = [
                    'course' => $dbCourse,
                    'billingInfo' => $billingCourses[$code] ?? ['type' => 'free'],
                    'transaction' => $trans[$code] ?? null,
                ];
            }
            $this->addFlash('success', 'Курсы успешно загружены!');
            return $this->render('course/index.html.twig', [
                'courses' => $authorizedCourses,
            ]);
        } catch (BillingException | BillingUnavailableException | \JsonException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->render('course/index.html.twig', [
                'courses' => [],
            ]);
        }
    }

    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        CourseRepository $repo,
        SerializerInterface $serializer,
        BillingClient $bc
    ): Response {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);
        /**
         * @var User $user
         */
        $user = $this->getUser();
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $dto = new CourseRequestDTO();
                $dto->code = $form->get('code')->getData();
                $dto->title = $form->get('name')->getData();
                $dto->type = $form->get('type')->getData();
                $dto->price = $form->get('price')->getData();

                $response = $bc->newCourse($serializer->serialize($dto, 'json'), $user->getApiToken());
                if ($response['success']) {
                    $repo->save($course, true);

                    return $this->redirectToRoute(
                        'app_course_show',
                        ['id' => $course->getId()],
                        Response::HTTP_SEE_OTHER
                    );
                } else {
                    $this->addFlash('error', $response['message']);
                    return $this->renderForm('course/new.html.twig', [
                        'course' => $course,
                        'form' => $form,
                    ]);
                }
            } catch (BillingException | BillingUnavailableException | \JsonException $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->renderForm('course/new.html.twig', [
                    'course' => $course,
                    'form' => $form,
                ]);
            }
        }
        return $this->renderForm('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/pay', name: 'app_course_pay', methods: ['GET'])]
    public function pay(
        Course $course,
        BillingClient $bc
    ): RedirectResponse {
        /**
         * @var User $user
         */
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash(
                'warning',
                'Зарегистрируйтесь или авторизуйтесь, чтобы получить доступ к этому курсу!'
            );

            return $this->redirectToRoute('app_login');
        }

        try {
            $response = $bc->pay($course->getCode(), $user->getApiToken());
            if (isset($response['success']) && $response['success']) {
                $this->addFlash(
                    'success',
                    'Курс успешно приобретен!'
                );
            } else {
                $this->addFlash(
                    'error',
                    $response['message']
                );
            }
        } catch (BillingException | \JsonException | BillingUnavailableException $e) {
            $this->addFlash(
                'error',
                $e->getMessage()
            );
        }
        return $this->redirectToRoute('app_course_index');
    }

    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(
        Course $course,
        BillingClient $bc,
    ): Response {
        try {
            $bCourse = $bc->course($course->getCode());
            if (is_null($bCourse) || $bCourse['type'] === 'free') {
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
            // получаем список транзакций пользователя по этому курсу, которые еще действительны
            $transaction = $bc->history([
                'type' => 'payment',
                'course_code' => $course->getCode(),
                'skip_expired' => true,
            ], $user->getApiToken());
            // если пользователь приобрел данный курс или если это АДМИН разрешаем остаться на странице
            if ($transaction || $this->isGranted('ROLE_SUPER_ADMIN')) {
                return $this->render('course/show.html.twig', [
                    'course' => $course,
                ]);
            }
            throw new AccessDeniedException('Данный курс вам недоступен!');
        } catch (BillingException | \JsonException | BillingUnavailableException | AccessDeniedException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_course_index');
        }
    }

    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Course $course,
        CourseRepository $repo,
        BillingClient $bc,
        SerializerInterface $serializer
    ): Response {
        $oldCourseCode = $repo->findOneBy(['id' => $course->getId()])->getCode();
        if (!$this->getUser()) {
            $this->addFlash(
                'warning',
                'Зарегистрируйтесь или авторизуйтесь, чтобы получить доступ к этой возможности!'
            );

            return $this->redirectToRoute('app_login');
        }

        try {
            $bCourse = $bc->course($course->getCode());
        } catch (BillingException | BillingUnavailableException | \JsonException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_course_show', ['id' => $oldCourseCode]);
        }
        /**
         * @var User $user
         */
        $user = $this->getUser();

        $form = $this->createForm(CourseType::class, $course, [
            'type' => $bCourse['type'],
            'price' => (float) $bCourse['price'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $dto = new CourseRequestDTO();
                $dto->code = $form->get('code')->getData();
                $dto->title = $form->get('name')->getData();
                $dto->type = $form->get('type')->getData();
                $dto->price = $form->get('price')->getData();

                $response = $bc->editCourse($oldCourseCode, $serializer->serialize($dto, 'json'), $user->getApiToken());

                if ($response['success']) {
                    $repo->save($course, true);

                    return $this->redirectToRoute(
                        'app_course_show',
                        ['id' => $course->getId()],
                        Response::HTTP_SEE_OTHER
                    );
                } else {
                    $this->addFlash('error', $response['message']);
                    return $this->renderForm('course/edit.html.twig', [
                        'course' => $course,
                        'form' => $form,
                    ]);
                }
            } catch (BillingException | BillingUnavailableException | \JsonException $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->renderForm('course/edit.html.twig', [
                    'course' => $course,
                    'form' => $form,
                ]);
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

    #[Route('/{id}/new/lesson', name: 'app_lesson_new', methods: ['GET', 'POST'])]
    public function newLesson(Request $request, Course $course, LessonRepository $lessonRepository): Response
    {
        $lesson = new Lesson();
        $lesson->setCourse($course);
        $form = $this->createForm(LessonType::class, $lesson, [
            'course' => $course,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lessonRepository->save($lesson, true);

            return $this->redirectToRoute(
                'app_course_show',
                ['id' => $course->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->renderForm('lesson/new.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
            'course' => $course,
        ]);
    }
}
