<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\Order;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use App\Repository\OrderRepository;
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
    private const MAX_QUANTITY = 10;
    private const MIN_QUANTITY = 1;

    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private CartRepository $cartRepository;
    private ProductRepository $productRepository;
    private OrderRepository $orderRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        CartRepository $cartRepository,
        ProductRepository $productRepository,
        OrderRepository $orderRepository
    ) {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->cartRepository = $cartRepository;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
    }

    #[Route('', name: 'app_cart_get', methods: ['GET'])]
    public function getCart(): JsonResponse
    {
        $user = $this->getUser();
        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->entityManager->persist($cart);
            $this->entityManager->flush();
        }

        $cartItems = $cart->getCartItems();
        $products = [];
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->getProduct();
            $products[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'quantity' => $cartItem->getQuantity()
            ];
        }

        return $this->json([
            'id' => $cart->getId(),
            'products' => $products
        ]);
    }

    #[Route('/products/{id}', name: 'app_cart_add_product', methods: ['POST'])]
    public function addProduct(int $id): JsonResponse
    {
        $user = $this->getUser();
        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->entityManager->persist($cart);
        }

        $product = $this->productRepository->find($id);
        if (!$product) {
            return $this->json(['error' => 'Produit non trouvé'], 404);
        }

        $cartItem = null;
        foreach ($cart->getCartItems() as $item) {
            if ($item->getProduct()->getId() === $id) {
                $cartItem = $item;
                break;
            }
        }

        if ($cartItem) {
            if ($cartItem->getQuantity() >= self::MAX_QUANTITY) {
                return $this->json(['error' => 'Quantité maximale atteinte'], 400);
            }
            $cartItem->setQuantity($cartItem->getQuantity() + 1);
        } else {
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setProduct($product);
            $cartItem->setQuantity(1);
            $this->entityManager->persist($cartItem);
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'Produit ajouté au panier']);
    }

    #[Route('/products/{id}', name: 'app_cart_update_product', methods: ['PUT'])]
    public function updateProductQuantity(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if (!$cart) {
            return $this->json(['error' => 'Panier non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['quantity']) || !is_numeric($data['quantity'])) {
            return $this->json(['error' => 'Quantité invalide'], 400);
        }

        $quantity = (int) $data['quantity'];
        if ($quantity < self::MIN_QUANTITY || $quantity > self::MAX_QUANTITY) {
            return $this->json(['error' => 'La quantité doit être entre ' . self::MIN_QUANTITY . ' et ' . self::MAX_QUANTITY], 400);
        }

        $cartItem = null;
        foreach ($cart->getCartItems() as $item) {
            if ($item->getProduct()->getId() === $id) {
                $cartItem = $item;
                break;
            }
        }

        if (!$cartItem) {
            return $this->json(['error' => 'Produit non trouvé dans le panier'], 404);
        }

        $cartItem->setQuantity($quantity);
        $this->entityManager->flush();

        return $this->json(['message' => 'Quantité mise à jour']);
    }

    #[Route('/products/{id}', name: 'app_cart_remove_product', methods: ['DELETE'])]
    public function removeProduct(int $id): JsonResponse
    {
        $user = $this->getUser();
        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if (!$cart) {
            return $this->json(['error' => 'Panier non trouvé'], 404);
        }

        $cartItem = null;
        foreach ($cart->getCartItems() as $item) {
            if ($item->getProduct()->getId() === $id) {
                $cartItem = $item;
                break;
            }
        }

        if (!$cartItem) {
            return $this->json(['error' => 'Produit non trouvé dans le panier'], 404);
        }

        $this->entityManager->remove($cartItem);
        $this->entityManager->flush();

        return $this->json(['message' => 'Produit retiré du panier']);
    }

    #[Route('/validate', name: 'app_cart_validate', methods: ['POST'])]
    public function validateCart(): JsonResponse
    {
        $user = $this->getUser();
        $cart = $this->cartRepository->findOneBy(['user' => $user]);

        if (!$cart) {
            return $this->json(['error' => 'Panier non trouvé'], 404);
        }

        $cartItems = $cart->getCartItems();
        if ($cartItems->isEmpty()) {
            return $this->json(['error' => 'Le panier est vide'], 400);
        }

        $order = new Order();
        $order->setUser($user);
        $order->setTotal(0);
        $this->entityManager->persist($order);

        $total = 0;
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->getProduct();
            $total += $product->getPrice() * $cartItem->getQuantity();
        }
        $order->setTotal($total);

        $this->entityManager->flush();

        // Vider le panier
        foreach ($cartItems as $cartItem) {
            $this->entityManager->remove($cartItem);
        }
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Commande créée avec succès',
            'order' => [
                'id' => $order->getId(),
                'total' => $order->getTotal(),
                'createdAt' => $order->getCreatedAt()->format('c')
            ]
        ]);
    }
}
