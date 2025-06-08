<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TestController extends AbstractController
{
    #[Route('/api/public', name: 'app_public', methods: ['GET'])]
    public function public(): JsonResponse
    {
        return $this->json([
            'message' => 'Cette route est publique',
            'user' => $this->getUser() ? $this->getUser()->getUserIdentifier() : null
        ]);
    }

    #[Route('/api/protected', name: 'app_protected', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function protected(): JsonResponse
    {
        return $this->json([
            'message' => 'Cette route est protégée',
            'user' => $this->getUser()->getUserIdentifier(),
            'roles' => $this->getUser()->getRoles()
        ]);
    }

    #[Route('/api/admin', name: 'app_admin', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function admin(): JsonResponse
    {
        return $this->json([
            'message' => 'Cette route est réservée aux administrateurs',
            'user' => $this->getUser()->getUserIdentifier(),
            'roles' => $this->getUser()->getRoles()
        ]);
    }
}
