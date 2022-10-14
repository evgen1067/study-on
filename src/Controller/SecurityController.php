<?php

namespace App\Controller;

use App\Dto\UserDto;
use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Form\RegisterType;
use App\Security\BillingAuthenticator;
use App\Service\BillingClient;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
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

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
    }

    /**
     * @param Request $request
     * @param UserAuthenticatorInterface $authenticator
     * @param BillingAuthenticator $formAuthenticator
     * @param BillingClient $billingClient
     * @return RedirectResponse|Response|null
     * @throws JsonException
     */
    #[Route(path: '/register', name: 'app_register')]
    public function register(
        Request $request,
        UserAuthenticatorInterface $authenticator,
        BillingAuthenticator $formAuthenticator,
        BillingClient $billingClient
    ): RedirectResponse|Response|null
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        $userDto = new UserDto();
        $form = $this->createForm(RegisterType::class, $userDto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user = $billingClient->register(json_encode($userDto, JSON_THROW_ON_ERROR));
            } catch (BillingUnavailableException $e) {
                // throw new BillingUnavailableException($e->getMessage());
                return $this->render('security/register.html.twig', [
                    'form' => $form->createView(),
                    'errors' => $e->getMessage(),
                ]);
            } catch(BillingException $e) {
                // throw new BillingException($e->getMessage());
                return $this->render('security/register.html.twig', [
                    'form' => $form->createView(),
                    'errors' => $e->getMessage(),
                ]);
            }

            return $authenticator->authenticateUser($user, $formAuthenticator, $request);
        }
        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
