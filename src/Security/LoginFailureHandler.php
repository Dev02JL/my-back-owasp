<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($request->getRequestFormat() === 'json') {
            return new JsonResponse([
                'error' => $exception->getMessageKey()
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Redirection par défaut pour les requêtes HTML
        return new Response('', Response::HTTP_UNAUTHORIZED);
    }
} 