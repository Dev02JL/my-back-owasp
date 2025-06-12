<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, RateLimiterFactory $loginLimiter): Response
    {
        $ip = $request->getClientIp();
        $limiter = $loginLimiter->create($ip);

        if (false === $limiter->consume(1)->isAccepted()) {
            return new JsonResponse([
                'error' => 'Trop de tentatives, veuillez patienter avant de réessayer.'
            ], 429);
        }

        if ($request->getContentType() === 'json' || $request->headers->get('Accept') === 'application/json') {
            if ($this->getUser()) {
                return new JsonResponse([
                    'message' => 'Déjà authentifié',
                    'user' => $this->getUser()->getUserIdentifier(),
                    'roles' => $this->getUser()->getRoles()
                ]);
            }

            $error = $authenticationUtils->getLastAuthenticationError();
            if ($error) {
                $errorMessage = 'Identifiants invalides';
                if ($error instanceof AccountStatusException) {
                    $errorMessage = 'Compte désactivé ou verrouillé';
                } elseif ($error instanceof BadCredentialsException) {
                    $errorMessage = 'Identifiants invalides';
                } elseif ($error instanceof UsernameNotFoundException) {
                    $errorMessage = 'Identifiants invalides';
                }
                return new JsonResponse([
                    'error' => $errorMessage
                ], Response::HTTP_UNAUTHORIZED);
            }

            return new JsonResponse([
                'message' => 'Authentification requise'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }

    #[Route('/api/check-auth', name: 'app_check_auth', methods: ['GET'])]
    public function checkAuth(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse([
                'authenticated' => false
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'authenticated' => true,
            'user' => [
                'email' => $user->getUserIdentifier(),
                'roles' => $user->getRoles()
            ]
        ]);
    }
}
