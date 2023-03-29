<?php

namespace App\Controller;

use App\DTO\Request\UserRequestDTO;
use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Exception\BillingValidationException;
use App\Form\RegisterType;
use App\Repository\CourseRepository;
use App\Security\BillingAuthenticator;
use App\Security\User;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_course_index');
        }
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
    }

    #[Route(path: 'register', name: 'app_register')]
    public function register(
        Request $req,
        BillingClient $bc,
        UserAuthenticatorInterface $authenticator,
        BillingAuthenticator $formAuthenticator
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        $dtoRequest = new UserRequestDTO();
        $form = $this->createForm(RegisterType::class, $dtoRequest);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user = $bc->register(json_encode($dtoRequest, JSON_THROW_ON_ERROR));
            } catch (BillingUnavailableException | BillingException | \JsonException $e) {
                $this->addFlash('error', $e->getMessage());

                return $this->render('security/register.html.twig', [
                    'form' => $form->createView(),
                ]);
            } catch (BillingValidationException $e) {
                try {
                    $errors = json_decode($e->getMessage(), true, 512, JSON_THROW_ON_ERROR);
                    foreach ($errors as $error) {
                        $this->addFlash('error', $error);
                    }
                    return $this->render('security/register.html.twig', [
                        'form' => $form->createView(),
                    ]);
                } catch (\JsonException $e) {
                    $this->addFlash('error', $e->getMessage());

                    return $this->render('security/register.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }
            }

            return $authenticator->authenticateUser($user, $formAuthenticator, $req);
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: 'profile', name: 'app_profile', methods: ['GET'])]
    public function profile(
        BillingClient $bc,
        CourseRepository $repo
    ): Response {
        /**
         * @var User $user
         */
        $user = $this->getUser();
        try {
            $responseDTO = $bc->profile($user->getApiToken());
            return $this->render('security/profile.html.twig', [
                'email' => $responseDTO->username,
                'role' => in_array('ROLE_SUPER_ADMIN', $responseDTO->roles, true) ? 'Администратор' : 'Пользователь',
                'balance' => $responseDTO->balance,
            ]);
        } catch (BillingException | BillingUnavailableException | \JsonException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_course_index');
        }
    }

    #[Route('/profile/history', name: 'app_profile_history', methods: ['GET'])]
    public function history(
        BillingClient $bc,
        CourseRepository $repo
    ): Response {
        try {
            /**
             * @var User $user
             */
            $user = $this->getUser();
            $transactions = $bc->history([], $user->getApiToken());

            usort($transactions, static function ($a, $b) {
                if ($a['created']['date'] === $b['created']['date']) {
                    return 0;
                }
                return ($a['created']['date'] > $b['created']['date']) ? 1 : -1;
            });
            foreach ($transactions as &$transaction) {
                if (isset($transaction['course_code'])) {
                    $course = $repo->findOneBy(['code' => $transaction['course_code']]);
                    $transaction['course'] = [
                        'id' => $course->getId(),
                        'name' => $course->getName(),
                    ];
                }
            }
            return $this->render('security/history.html.twig', [
                'transactions' => $transactions,
            ]);
        } catch (BillingException | BillingUnavailableException | \JsonException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_course_index');
        }
    }
}
