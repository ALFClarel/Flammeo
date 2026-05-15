<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setPageTitle(Crud::PAGE_INDEX, 'Commandes')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail de la commande')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
            ->onlyOnIndex();

        yield TextField::new('status', 'Statut');

        yield MoneyField::new('totalAmount', 'Total')
            ->setCurrency('EUR')
            ->setStoredAsCents();

        yield TextField::new('customerEmail', 'Email cliente')
            ->hideOnForm();

        yield TextField::new('stripeSessionId', 'Session Stripe')
            ->hideOnIndex()
            ->hideOnForm();

        yield AssociationField::new('orderItems', 'Produits commandés')
            ->onlyOnDetail();

        yield DateTimeField::new('createdAt', 'Créée le')
            ->hideOnForm();

        yield DateTimeField::new('paidAt', 'Payée le')
            ->hideOnForm();
    }
}
