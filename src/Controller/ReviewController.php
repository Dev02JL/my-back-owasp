<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Review;
use App\Repository\ProductRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products/{productId}/reviews')]
class ReviewController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
        private ReviewRepository $reviewRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private TokenStorageInterface $tokenStorage
    ) {}

    #[Route('', name: 'app_review_list', methods: ['GET'])]
    public function list(int $productId): JsonResponse
    {
        $product = $this->productRepository->find($productId);
        
        if (!$product) {
            return new JsonResponse(['message' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $reviews = $product->getReviews();
        $data = $this->serializer->serialize($reviews, 'json', ['groups' => 'review:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('', name: 'app_review_create', methods: ['POST'])]
    public function create(int $productId, Request $request): JsonResponse
    {
        $product = $this->productRepository->find($productId);
        
        if (!$product) {
            return new JsonResponse(['message' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->tokenStorage->getToken()->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        $review = new Review();
        $review->setRating($data['rating'] ?? 0);
        $review->setMessage($data['message'] ?? null);
        $review->setUser($user);
        $review->setProduct($product);

        $errors = $this->validator->validate($review);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($review);
        $this->entityManager->flush();

        $data = $this->serializer->serialize($review, 'json', ['groups' => 'review:read']);
        return new JsonResponse($data, Response::HTTP_CREATED, [], true);
    }

    #[Route('/{id}', name: 'app_review_update', methods: ['PUT'])]
    public function update(int $productId, int $id, Request $request): JsonResponse
    {
        $review = $this->reviewRepository->find($id);
        
        if (!$review) {
            return new JsonResponse(['message' => 'Avis non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if ($review->getProduct()->getId() !== $productId) {
            return new JsonResponse(['message' => 'Avis non trouvé pour ce produit'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->tokenStorage->getToken()->getUser();
        if (!$user || $review->getUser()->getId() !== $user->getId()) {
            return new JsonResponse(['message' => 'Non autorisé à modifier cet avis'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return new JsonResponse(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['rating'])) {
            $review->setRating($data['rating']);
        }
        if (isset($data['message'])) {
            $review->setMessage($data['message']);
        }

        $errors = $this->validator->validate($review);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        $data = $this->serializer->serialize($review, 'json', ['groups' => 'review:read']);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'app_review_delete', methods: ['DELETE'])]
    public function delete(int $productId, int $id): JsonResponse
    {
        $review = $this->reviewRepository->find($id);
        
        if (!$review) {
            return new JsonResponse(['message' => 'Avis non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if ($review->getProduct()->getId() !== $productId) {
            return new JsonResponse(['message' => 'Avis non trouvé pour ce produit'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->tokenStorage->getToken()->getUser();
        if (!$user || $review->getUser()->getId() !== $user->getId()) {
            return new JsonResponse(['message' => 'Non autorisé à supprimer cet avis'], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($review);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
} 