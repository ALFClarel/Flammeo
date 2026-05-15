<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->onlyOnIndex(),

            TextField::new('status', 'Statut'),

            MoneyField::new('totalAmount', 'Total')
                ->setCurrency('EUR')
                ->setStoredAsCents(),

            TextField::new('customerEmail', 'Email cliente')
                ->hideOnForm(),

            TextField::new('stripeSessionId', 'Session Stripe')
                ->hideOnForm()
                ->hideOnIndex(),

            DateTimeField::new('createdAt', 'Créée le')
                ->hideOnForm(),

            DateTimeField::new('paidAt', 'Payée le')
                ->hideOnForm(),
        ];
    }

}
