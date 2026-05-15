<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StripeWebhookController extends AbstractController
{
    #[Route('/stripe/webhook', name: 'app_stripe_webhook', methods: ['POST'])]
    public function handle(
        Request $request,
        EntityManagerInterface $entityManager,
        string $stripeWebhookSecret,
    ): Response {
        $payload = $request->getContent();
        $signature = $request->headers->get('stripe-signature');

        if (!$signature) {
            return new Response('Missing Stripe signature', Response::HTTP_BAD_REQUEST);
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                $stripeWebhookSecret
            );
        } catch (\UnexpectedValueException) {
            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        } catch (SignatureVerificationException) {
            return new Response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            $orderId = $session->metadata->order_id ?? null;

            if (!$orderId) {
                return new Response('Missing order id', Response::HTTP_BAD_REQUEST);
            }

            /** @var Order|null $order */
            $order = $entityManager->getRepository(Order::class)->find($orderId);

            if (!$order) {
                return new Response('Order not found', Response::HTTP_NOT_FOUND);
            }

            if ($order->getStatus() === 'paid') {
                return new Response('Already processed', Response::HTTP_OK);
            }

            $order->setStatus('paid');
            $order->setPaidAt(new \DateTimeImmutable());

            foreach ($order->getOrderItems() as $orderItem) {
                $product = $orderItem->getProduct();

                if (!$product instanceof Product) {
                    continue;
                }

                $product->setStatus(Product::STATUS_SOLD);
                $product->setSoldAt(new \DateTimeImmutable());
                $product->setReservedAt(null);
            }

            if (method_exists($order, 'setCustomerEmail')) {
                $order->setCustomerEmail($session->customer_details->email ?? null);
            }

            $entityManager->flush();
        }

        return new Response('Webhook handled', Response::HTTP_OK);
    }
}
