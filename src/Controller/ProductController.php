<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    private const MAX_TITLE_LENGTH = 255;
    private const MIN_TITLE_LENGTH = 3;
    private const MAX_IMAGE_URL_LENGTH = 2048;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private AuthorizationCheckerInterface $authorizationChecker
    ) {}

    #[Route('', name: 'app_product_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $products = $this->productRepository->findAll();
            $data = $this->serializer->serialize($products, 'json', ['groups' => 'product:read']);
            return new JsonResponse($data, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Une erreur est survenue lors de la récupération des produits'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $product = $this->productRepository->find($id);
            
            if (!$product) {
                return new JsonResponse(['message' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
            }

            $data = $this->serializer->serialize($product, 'json', ['groups' => 'product:read']);
            return new JsonResponse($data, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Une erreur est survenue lors de la récupération du produit'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'app_product_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
            }

            // Validation des champs requis
            if (!isset($data['title']) || !isset($data['price'])) {
                return new JsonResponse(['message' => 'Titre et prix requis'], Response::HTTP_BAD_REQUEST);
            }

            // Validation du titre
            if (strlen($data['title']) < self::MIN_TITLE_LENGTH || strlen($data['title']) > self::MAX_TITLE_LENGTH) {
                return new JsonResponse([
                    'message' => 'Le titre doit faire entre ' . self::MIN_TITLE_LENGTH . ' et ' . self::MAX_TITLE_LENGTH . ' caractères'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validation du prix
            if (!is_numeric($data['price']) || $data['price'] < 0) {
                return new JsonResponse(['message' => 'Le prix doit être un nombre positif'], Response::HTTP_BAD_REQUEST);
            }

            $product = new Product();
            $product->setTitle($data['title']);
            $product->setPrice((float) $data['price']);

            // Gestion de l'image si présente
            if (isset($data['image'])) {
                if (strlen($data['image']) > self::MAX_IMAGE_URL_LENGTH) {
                    return new JsonResponse([
                        'message' => 'L\'URL de l\'image est trop longue (max ' . self::MAX_IMAGE_URL_LENGTH . ' caractères)'
                    ], Response::HTTP_BAD_REQUEST);
                }
                // Validation de l'URL de l'image
                if (!filter_var($data['image'], FILTER_VALIDATE_URL)) {
                    return new JsonResponse(['message' => 'URL d\'image invalide'], Response::HTTP_BAD_REQUEST);
                }
                $product->setImage($data['image']);
            }

            $errors = $this->validator->validate($product);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($product);
            $this->entityManager->flush();

            $data = $this->serializer->serialize($product, 'json', ['groups' => 'product:read']);
            return new JsonResponse($data, Response::HTTP_CREATED, [], true);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Une erreur est survenue lors de la création du produit'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'app_product_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $product = $this->productRepository->find($id);
            
            if (!$product) {
                return new JsonResponse(['message' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
            }

            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
            }

            if (isset($data['title'])) {
                if (strlen($data['title']) < self::MIN_TITLE_LENGTH || strlen($data['title']) > self::MAX_TITLE_LENGTH) {
                    return new JsonResponse([
                        'message' => 'Le titre doit faire entre ' . self::MIN_TITLE_LENGTH . ' et ' . self::MAX_TITLE_LENGTH . ' caractères'
                    ], Response::HTTP_BAD_REQUEST);
                }
                $product->setTitle($data['title']);
            }

            if (isset($data['price'])) {
                if (!is_numeric($data['price']) || $data['price'] < 0) {
                    return new JsonResponse(['message' => 'Le prix doit être un nombre positif'], Response::HTTP_BAD_REQUEST);
                }
                $product->setPrice((float) $data['price']);
            }

            if (isset($data['image'])) {
                if (strlen($data['image']) > self::MAX_IMAGE_URL_LENGTH) {
                    return new JsonResponse([
                        'message' => 'L\'URL de l\'image est trop longue (max ' . self::MAX_IMAGE_URL_LENGTH . ' caractères)'
                    ], Response::HTTP_BAD_REQUEST);
                }
                // Validation de l'URL de l'image
                if (!filter_var($data['image'], FILTER_VALIDATE_URL)) {
                    return new JsonResponse(['message' => 'URL d\'image invalide'], Response::HTTP_BAD_REQUEST);
                }
                $product->setImage($data['image']);
            }

            $errors = $this->validator->validate($product);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            $data = $this->serializer->serialize($product, 'json', ['groups' => 'product:read']);
            return new JsonResponse($data, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Une erreur est survenue lors de la mise à jour du produit'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        try {
            $product = $this->productRepository->find($id);
            
            if (!$product) {
                return new JsonResponse(['message' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($product);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Produit supprimé avec succès'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Une erreur est survenue lors de la suppression du produit'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 