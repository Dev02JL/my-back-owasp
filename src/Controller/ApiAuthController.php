<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthController
{
    #[Route('/api/login_check', name: 'api_login_check', methods: ['POST'])]
    public function loginCheck(Request $request): Response
    {
        // Cette méthode ne sera jamais appelée, car LexikJWT s'occupe de l'authentification.
        return new Response('', Response::HTTP_NOT_FOUND);
    }
} 