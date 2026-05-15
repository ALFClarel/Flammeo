<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private const CART_SESSION_KEY = 'cart';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ProductRepository $productRepository,
    ) {
    }

    public function add(Product $product): void
    {
        $cart = $this->getCart();

        // Produit unique : quantité forcée à 1
        $cart[$product->getId()] = 1;

        $this->saveCart($cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->getCart();

        unset($cart[$productId]);

        $this->saveCart($cart);
    }

    public function clear(): void
    {
        $this->saveCart([]);
    }

    public function getCart(): array
    {
        return $this->requestStack
            ->getSession()
            ->get(self::CART_SESSION_KEY, []);
    }

    public function getItems(): array
    {
        $items = [];

        foreach ($this->getCart() as $productId => $quantity) {
            $product = $this->productRepository->find($productId);

            if (!$product) {
                continue;
            }

            $items[] = [
                'product' => $product,
                'quantity' => $quantity,
                'total' => $product->getPrice() * $quantity,
            ];
        }

        return $items;
    }

    public function getTotal(): float
    {
        $total = 0;

        foreach ($this->getItems() as $item) {
            $total += $item['total'];
        }

        return $total;
    }

    public function count(): int
    {
        return count($this->getCart());
    }

    private function saveCart(array $cart): void
    {
        $this->requestStack
            ->getSession()
            ->set(self::CART_SESSION_KEY, $cart);
    }
}
