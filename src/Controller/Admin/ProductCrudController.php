<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Bougie')
            ->setEntityLabelInPlural('Bougies')
            ->setPageTitle(Crud::PAGE_INDEX, 'Bougies')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter une bougie')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier une bougie');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();

        yield TextField::new('name', 'Nom');

        yield MoneyField::new('price', 'Prix')
            ->setCurrency('EUR')
            ->setStoredAsCents(false);

        yield ImageField::new('imageName', 'Image')
            ->setBasePath('/uploads/products')
            ->setUploadDir('public/uploads/products')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->setRequired(false)
            ->setFormTypeOption('attr', [
                'accept' => 'image/*',
                'capture' => 'environment',
            ]);

        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'Disponible' => Product::STATUS_AVAILABLE,
                'Réservée' => Product::STATUS_RESERVED,
                'Vendue' => Product::STATUS_SOLD,
            ]);

        yield DateTimeField::new('createdAt', 'Créée le')
            ->hideOnForm();

        yield DateTimeField::new('soldAt', 'Vendue le')
            ->hideOnForm();
    }
}
