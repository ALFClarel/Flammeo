<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findBy(
            ['status' => Product::STATUS_AVAILABLE],
            ['createdAt' => 'DESC']
        );

        return $this->render('home/index.html.twig', [
            'products' => $products,
        ]);
    }
}
