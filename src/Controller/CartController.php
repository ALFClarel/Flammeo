<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panier')]
class CartController extends AbstractController
{
    #[Route('', name: 'app_cart_index', methods: ['GET'])]
    public function index(CartService $cartService): Response
    {
        return $this->render('cart/index.html.twig', [
            'items' => $cartService->getItems(),
            'total' => $cartService->getTotal(),
        ]);
    }

    #[Route('/ajouter/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Product $product, Request $request, CartService $cartService): Response
    {
        if (!$this->isCsrfTokenValid('cart_add_' . $product->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        if ($product->getStatus() !== Product::STATUS_AVAILABLE) {
            $this->addFlash('danger', 'Cette bougie n’est plus disponible.');

            return $this->redirectToRoute('app_home');
        }

        $cartService->add($product);

        $this->addFlash('success', 'La bougie a été ajoutée au panier.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/retirer/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(Product $product, Request $request, CartService $cartService): Response
    {
        if (!$this->isCsrfTokenValid('cart_remove_' . $product->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $cartService->remove($product->getId());

        $this->addFlash('success', 'La bougie a été retirée du panier.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/vider', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(Request $request, CartService $cartService): Response
    {
        if (!$this->isCsrfTokenValid('cart_clear', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $cartService->clear();

        $this->addFlash('success', 'Le panier a été vidé.');

        return $this->redirectToRoute('app_cart_index');
    }
}
