<?php

namespace App\Controller;

use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Repository\CourseRepository;
use App\Security\User;
use App\Service\BillingClient;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class ProfileController extends AbstractController
{
    /**
     * @throws BillingUnavailableException
     * @throws JsonException
     * @throws BillingException
     */
    #[Route('/profile', name: 'app_profile')]
    public function index(BillingClient $billingClient): Response
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();
        $dto = $billingClient->currentUser($user->getApiToken());

        return $this->render('profile/index.html.twig', [
            'email' => $dto->username,
            'role' => in_array('ROLE_SUPER_ADMIN', $dto->roles, true) ? 'Администратор' : 'Пользователь',
            'balance' => $dto->balance,
        ]);
    }

    #[Route('/profile/history', name: 'app_profile')]
    public function history(BillingClient $billingClient, CourseRepository $courseRepository): Response
    {
        try {
            /**
             * @var User $user
             */
            $user = $this->getUser();

            $transactions = $billingClient->getTransactions([], $user->getApiToken());

            usort($transactions, static function ($a, $b) {
                if($a['created'] === $b['created']) {
                    return 0;
                }
                return ($a['created'] > $b['created']) ? 1 : -1;
            });

            foreach ($transactions as &$transaction) {
                if (isset($transaction['course_code'])) {
                    $course = $courseRepository->findOneBy(['code' => $transaction['course_code']]);
                    $transaction['course'] = [
                        'id' => $course->getId(),
                        'name' => $course->getName(),
                    ];
                }
            }

            return $this->render('profile/history.html.twig', [
                'transactions' => $transactions,
            ]);
        } catch (BillingException|JsonException|BillingUnavailableException $e) {
            $this->addFlash(
                'error',
                $e->getMessage()
            );
            return $this->redirectToRoute('app_course_index');
        }

    }
}
