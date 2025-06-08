<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        // Si la requête est en JSON ou si l'utilisateur est déjà authentifié
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
                return new JsonResponse([
                    'error' => $error->getMessageKey()
                ], Response::HTTP_UNAUTHORIZED);
            }

            return new JsonResponse([
                'message' => 'Authentification requise'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // controller can be blank: it will never be called!
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }
}
