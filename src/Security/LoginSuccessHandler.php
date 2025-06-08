<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();

        if ($request->getRequestFormat() === 'json') {
            return new JsonResponse([
                'message' => 'Authentification réussie',
                'user' => $user->getUserIdentifier(),
                'roles' => $user->getRoles()
            ]);
        }

        // Redirection par défaut pour les requêtes HTML
        return new Response('', Response::HTTP_OK);
    }
} 