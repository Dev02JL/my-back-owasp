<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProtectedController extends AbstractController
{
    #[Route('/api/protected', name: 'api_protected', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Vous avez accès à cette route protégée',
            'user' => $this->getUser()->getUserIdentifier()
        ]);
    }
} 