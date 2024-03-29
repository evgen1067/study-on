<?php

namespace App\Security;

use App\Exception\BillingException;
use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use Exception;
use JsonException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class BillingAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private BillingClient $billingClient;

    public function __construct(private UrlGeneratorInterface $urlGenerator, BillingClient $billingClient)
    {
        $this->billingClient = $billingClient;
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        try {
            $email = $request->request->get('email', '');
            $password = $request->request->get('password', '');
            $request->getSession()->set(Security::LAST_USERNAME, $email);
            $credentials = json_encode([
                'username' => $email,
                'password' => $password,
            ], JSON_THROW_ON_ERROR);

            return new SelfValidatingPassport(
                new UserBadge($credentials, function ($credentials) {
                    try {
                        return $this->billingClient->auth($credentials);
                    } catch (JsonException | BillingException | BillingUnavailableException $e) {
                        throw new CustomUserMessageAuthenticationException($e->getMessage());
                    }
                }),
                [
                    new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                    new RememberMeBadge(),
                ]
            );
        } catch (JsonException $e) {
            throw new CustomUserMessageAuthenticationException($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        // For example:
        // return new RedirectResponse($this->urlGenerator->generate('some_route'));
        return new RedirectResponse($this->urlGenerator->generate('app_course_index'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
