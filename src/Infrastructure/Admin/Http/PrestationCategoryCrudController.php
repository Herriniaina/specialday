<?php

namespace App\Infrastructure\Admin\Http;

use App\Infrastructure\PrestationCategory\Entity\PrestationCategory;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<PrestationCategory>
 */
class PrestationCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PrestationCategory::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Liste des catégories de préstations')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter une catégorie')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier la catégorie')
        ;
    }
}
