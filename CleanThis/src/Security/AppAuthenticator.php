<?php

namespace App\Security;

use App\Service\PostLogsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    private $postLogsService;
    private $logger;

    use TargetPathTrait;

    public const LOGIN_ROUTE = 'auth_oauth_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator,PostLogsService $postLogsService, LoggerInterface $logger)
    {
        $this->postLogsService =  $postLogsService;
        $this->logger = $logger;
    }

    public function authenticate(Request $request): Passport
    {
   
        $email = $request->request->get('email', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );

    }



    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        $user=$token->getUser(); 
        try {
                
            $this->postLogsService->postConnexionInfos(
                'cnxApp',
                'L\'utilisateur s\'est connecté',
                'INFO',
                [],
                $user->getEmail()
            );

        } catch (\Throwable $th) {
            return new RedirectResponse($this->urlGenerator->generate('auth_oauth_login'));
        }
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {

            return new RedirectResponse($this->urlGenerator->generate('app_admin_operation_profil'));
        }elseif (in_array('ROLE_SENIOR', $user->getRoles(), true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_admin_operation_profil'));
        }elseif (in_array('ROLE_APPRENTI', $user->getRoles(), true)) {
            return new RedirectResponse($this->urlGenerator->generate('app_admin_operation_profil'));
        }else {
            return new RedirectResponse($this->urlGenerator->generate('app_user_profil'));
        }
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        // Stocker l'exception d'authentification dans la session
        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        }

        // Récupérer le compteur de tentatives de connexion infructueuses
        $failedLoginAttempts = $request->getSession()->get('failed_login_attempts', 0);

        // Augmenter le compteur
        $request->getSession()->set('failed_login_attempts', ++$failedLoginAttempts);

        // Si le compteur atteint 3, enregistrer un log et bloquer la connexion
        if ($failedLoginAttempts > 3) {
            try {
                $this->postLogsService->postConnexionInfos(
                    'alertApp',
                    'L\'utilisateur s\'est trompé de mot de passe à plus de 3 reprises',
                    'Warning',
                    [],
                    $request->request->get('email')
                );

            } catch (\Exception $e) {
                  $this->logger->error('Failed to log user login: ' . $e->getMessage());
            }
          

            throw new CustomUserMessageAuthenticationException('Trop de tentatives de connexion infructueuses.');
        }

        // Récupérer l'URL de connexion
        $url = $this->getLoginUrl($request);

        // Rediriger vers l'URL de connexion
        return new RedirectResponse($url);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}