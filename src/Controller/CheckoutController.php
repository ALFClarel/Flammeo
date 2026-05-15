<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'app_checkout', methods: ['POST'])]
    public function checkout(
        Request $request,
        CartService $cartService,
        EntityManagerInterface $entityManager,
        string $stripeSecretKey,
    ): Response {

        if (!$this->isCsrfTokenValid('checkout', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $items = $cartService->getItems();

        if ($items === []) {
            $this->addFlash('danger', 'Ton panier est vide.');

            return $this->redirectToRoute('app_cart_index');
        }

        $lineItems = [];
        $totalAmount = 0;

        $entityManager->beginTransaction();

        try {
            $order = new Order();
            $order->setStatus('pending');

            foreach ($items as $item) {
                /** @var Product $product */
                $product = $item['product'];

                $freshProduct = $entityManager
                    ->getRepository(Product::class)
                    ->find($product->getId());

                if (!$freshProduct || $freshProduct->getStatus() !== Product::STATUS_AVAILABLE) {
                    throw new \RuntimeException(sprintf(
                        'La bougie "%s" n’est plus disponible.',
                        $product->getName()
                    ));
                }

                $freshProduct->setStatus(Product::STATUS_RESERVED);
                $freshProduct->setReservedAt(new \DateTimeImmutable());

                $orderItem = new OrderItem();
                $orderItem->setProduct($freshProduct);
                $orderItem->setOrderr($order);
                $orderItem->setPrice($freshProduct->getPrice());

                $order->addOrderItem($orderItem);

                $lineItems[] = [
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => (int) round($freshProduct->getPrice() * 100),
                        'product_data' => [
                            'name' => $freshProduct->getName(),
                        ],
                    ],
                ];

                $totalAmount += (int) round($freshProduct->getPrice() * 100);

                $entityManager->persist($orderItem);
            }

            $order->setTotalAmount($totalAmount);

            $entityManager->persist($order);
            $entityManager->flush();

            Stripe::setApiKey($stripeSecretKey);

            $session = Session::create([
                'mode' => 'payment',
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'success_url' => $this->generateUrl(
                        'app_checkout_success',
                        [],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    ) . '?session_id={CHECKOUT_SESSION_ID}',

                'cancel_url' => $this->generateUrl(
                    'app_checkout_cancel',
                    [],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                'metadata' => [
                    'order_id' => (string) $order->getId(),
                ],
            ]);

            $order->setStripeSessionId($session->id);

            $entityManager->flush();
            $entityManager->commit();

            return $this->redirect($session->url, 303);
        } catch (\Throwable $exception) {
            $entityManager->rollback();

            $this->addFlash('danger', $exception->getMessage());

            return $this->redirectToRoute('app_cart_index');
        }
    }

    #[Route('/checkout/success', name: 'app_checkout_success', methods: ['GET'])]
    public function success(CartService $cartService): Response
    {
        $cartService->clear();

        return $this->render('checkout/success.html.twig');
    }

    #[Route('/checkout/cancel', name: 'app_checkout_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        $this->addFlash('danger', 'Le paiement a été annulé.');

        return $this->redirectToRoute('app_cart_index');
    }
}
