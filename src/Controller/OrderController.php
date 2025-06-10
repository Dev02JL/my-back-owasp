<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/orders')]
#[IsGranted('ROLE_USER')]
class OrderController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderRepository $orderRepository,
        private SerializerInterface $serializer
    ) {}

    #[Route('', name: 'app_order_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $user = $this->getUser();
            $orders = $user->getOrders();

            $data = $this->serializer->serialize($orders, 'json', ['groups' => 'order:read']);
            return new JsonResponse($data, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Une erreur est survenue lors de la récupération des commandes',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->getUser();
            $order = $this->orderRepository->find($id);

            if (!$order) {
                return new JsonResponse([
                    'error' => 'Commande non trouvée',
                ], Response::HTTP_NOT_FOUND);
            }

            if ($order->getUser()->getId() !== $user->getId()) {
                return new JsonResponse([
                    'error' => 'Vous n\'êtes pas autorisé à voir cette commande',
                ], Response::HTTP_FORBIDDEN);
            }

            $data = $this->serializer->serialize($order, 'json', ['groups' => 'order:read']);
            return new JsonResponse($data, Response::HTTP_OK, [], true);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Une erreur est survenue lors de la récupération de la commande',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 