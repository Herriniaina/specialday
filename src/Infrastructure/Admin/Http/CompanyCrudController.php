<?php

namespace App\Infrastructure\Admin\Http;

use App\Infrastructure\Company\Entity\Company;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Company>
 */
class CompanyCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Company::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            TextField::new('slug'),
            TextEditorField::new('nif'),
            TextEditorField::new('stat'),
            TextEditorField::new('rcs'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Liste des entreprises')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter une entreprise')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier l\'entreprise')
        ;
    }
}
