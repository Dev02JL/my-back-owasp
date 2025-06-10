<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;

class TokenController extends AbstractController
{
    #[Route('/api/token/refresh', name: 'api_token_refresh', methods: ['POST'])]
    public function refresh(
        Request $request,
        RefreshTokenGeneratorInterface $refreshTokenGenerator,
        RefreshTokenManagerInterface $refreshTokenManager,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['refresh_token'])) {
            return $this->json(['message' => 'Refresh token manquant'], 400);
        }

        $refreshToken = $refreshTokenManager->get($data['refresh_token']);
        
        if (!$refreshToken || !$refreshToken->isValid()) {
            return $this->json(['message' => 'Refresh token invalide'], 401);
        }

        $userEmail = $refreshToken->getUsername();
        if (!$userEmail) {
            return $this->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        // Récupérer l'entité User
        $userEntity = $this->getDoctrine()->getRepository('App:User')->findOneBy(['email' => $userEmail]);
        if (!$userEntity) {
            return $this->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        // Invalider l'ancien refresh token
        $refreshTokenManager->delete($refreshToken);

        // Créer un nouveau refresh token
        $newRefreshToken = $refreshTokenGenerator->createForUserWithTtl($userEntity, 2592000);
        $refreshTokenManager->save($newRefreshToken);

        // Générer un nouveau token JWT
        return $this->json([
            'token' => $jwtManager->create($userEntity),
            'refresh_token' => $newRefreshToken->getRefreshToken()
        ]);
    }
} 