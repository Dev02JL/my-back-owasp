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
use Symfony\Component\HttpFoundation\Response;

class TokenController extends AbstractController
{
    #[Route('/api/token/refresh', name: 'api_token_refresh', methods: ['POST'])]
    public function refresh(
        Request $request,
        RefreshTokenGeneratorInterface $refreshTokenGenerator,
        RefreshTokenManagerInterface $refreshTokenManager,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['refresh_token'])) {
                return $this->json([
                    'error' => 'Refresh token manquant'
                ], Response::HTTP_BAD_REQUEST);
            }

            $refreshToken = $refreshTokenManager->get($data['refresh_token']);
            
            if (!$refreshToken) {
                return $this->json([
                    'error' => 'Refresh token invalide'
                ], Response::HTTP_UNAUTHORIZED);
            }

            if (!$refreshToken->isValid()) {
                // Supprimer le token invalide
                $refreshTokenManager->delete($refreshToken);
                return $this->json([
                    'error' => 'Refresh token expiré'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $userEmail = $refreshToken->getUsername();
            if (!$userEmail) {
                return $this->json([
                    'error' => 'Utilisateur non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Récupérer l'entité User
            $userEntity = $this->getDoctrine()->getRepository('App:User')->findOneBy(['email' => $userEmail]);
            if (!$userEntity) {
                // Supprimer le token si l'utilisateur n'existe plus
                $refreshTokenManager->delete($refreshToken);
                return $this->json([
                    'error' => 'Utilisateur non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Vérifier si l'utilisateur est actif
            if (!$userEntity->isEnabled()) {
                // Supprimer le token si l'utilisateur est désactivé
                $refreshTokenManager->delete($refreshToken);
                return $this->json([
                    'error' => 'Compte désactivé'
                ], Response::HTTP_FORBIDDEN);
            }

            // Invalider l'ancien refresh token
            $refreshTokenManager->delete($refreshToken);

            // Créer un nouveau refresh token avec une durée de validité de 30 jours
            $newRefreshToken = $refreshTokenGenerator->createForUserWithTtl($userEntity, 2592000);
            $refreshTokenManager->save($newRefreshToken);

            // Générer un nouveau token JWT avec une durée de validité de 1 heure
            $jwtToken = $jwtManager->create($userEntity);

            return $this->json([
                'token' => $jwtToken,
                'refresh_token' => $newRefreshToken->getRefreshToken(),
                'expires_in' => 3600 // 1 heure en secondes
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors du rafraîchissement du token'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 