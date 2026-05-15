<?php

namespace App\Command;

use App\Entity\Order;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:release-expired-reservations',
    description: 'Release products reserved for unpaid expired orders',
)]
class ReleaseExpiredReservationsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $expirationDate = new \DateTimeImmutable('-30 minutes');

        $orders = $this->entityManager
            ->getRepository(Order::class)
            ->createQueryBuilder('o')
            ->andWhere('o.status = :status')
            ->andWhere('o.createdAt < :expirationDate')
            ->setParameter('status', 'pending')
            ->setParameter('expirationDate', $expirationDate)
            ->getQuery()
            ->getResult();

        $releasedProducts = 0;
        $expiredOrders = 0;

        foreach ($orders as $order) {
            $order->setStatus('expired');
            $expiredOrders++;

            foreach ($order->getOrderItems() as $orderItem) {
                $product = $orderItem->getProduct();

                if (!$product instanceof Product) {
                    continue;
                }

                if ($product->getStatus() !== Product::STATUS_RESERVED) {
                    continue;
                }

                $product->setStatus(Product::STATUS_AVAILABLE);
                $product->setReservedAt(null);

                $releasedProducts++;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            '%d commande(s) expirée(s), %d produit(s) remis en vente.',
            $expiredOrders,
            $releasedProducts
        ));

        return Command::SUCCESS;
    }
}
