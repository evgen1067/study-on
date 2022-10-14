<?php

namespace App\Controller;

use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class ProfileController extends AbstractController
{
    private Security $security;

    public function __construct(Security $security)
    {
        // Avoid calling getUser() in the constructor: auth may not
        // be complete yet. Instead, store the entire Security object.
        $this->security = $security;
    }

    /**
     * @throws BillingUnavailableException
     * @throws JsonException
     * @throws BillingException
     */
    #[Route('/profile', name: 'app_profile')]
    public function index(BillingClient $billingClient): Response
    {
        $dto = $billingClient->currentUser($this->security->getUser()->getApiToken());

        return $this->render('profile/index.html.twig', [
            'email' => $dto->username,
            'role' => in_array('ROLE_SUPER_ADMIN', $dto->roles, true) ? 'Администратор' : 'Пользователь',
            'balance' => $dto->balance,
        ]);
    }
}
