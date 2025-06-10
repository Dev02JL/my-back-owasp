<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\Product;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/cart')]
#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    #[Route('', name: 'app_cart_get', methods: ['GET'])]
    public function getCart(): JsonResponse
    {
        try {
            $user = $this->getUser();
            $cart = $user->getCart();

            if (!$cart) {
                $cart = new Cart();
                $cart->setUser($user);
                $this->entityManager->persist($cart);
                $this->entityManager->flush();
            }

            return $this->json([
                'id' => $cart->getId(),
                'products' => $cart->getProducts()->map(fn(Product $product) => [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'price' => $product->getPrice(),
                ])->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la récupération du panier',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/products/{id}', name: 'app_cart_add_product', methods: ['POST'])]
    public function addProduct(Product $product): JsonResponse
    {
        try {
            $user = $this->getUser();
            $cart = $user->getCart();

            if (!$cart) {
                $cart = new Cart();
                $cart->setUser($user);
            }

            $cart->addProduct($product);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Produit ajouté au panier',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de l\'ajout du produit',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/products/{id}', name: 'app_cart_remove_product', methods: ['DELETE'])]
    public function removeProduct(Product $product): JsonResponse
    {
        try {
            $user = $this->getUser();
            $cart = $user->getCart();

            if (!$cart) {
                return $this->json([
                    'error' => 'Le panier est vide',
                ], Response::HTTP_NOT_FOUND);
            }

            $cart->removeProduct($product);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Produit retiré du panier',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la suppression du produit',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/validate', name: 'app_cart_validate', methods: ['POST'])]
    public function validateCart(): JsonResponse
    {
        try {
            $user = $this->getUser();
            $cart = $user->getCart();

            if (!$cart || $cart->getProducts()->isEmpty()) {
                return $this->json([
                    'error' => 'Le panier est vide',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Créer la commande
            $order = new Order();
            $order->setUser($user);
            
            // Calculer le total et ajouter les produits
            $total = 0;
            foreach ($cart->getProducts() as $product) {
                $order->addProduct($product);
                $total += $product->getPrice();
            }
            $order->setTotal($total);

            // Vider le panier
            foreach ($cart->getProducts() as $product) {
                $cart->removeProduct($product);
            }

            $this->entityManager->persist($order);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Commande créée avec succès',
                'order' => [
                    'id' => $order->getId(),
                    'total' => $order->getTotal(),
                    'createdAt' => $order->getCreatedAt(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la validation de la commande',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
