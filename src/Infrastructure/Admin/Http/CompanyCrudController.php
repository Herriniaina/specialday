<?php

namespace App\Infrastructure\Admin\Http;

use App\Infrastructure\Company\Entity\Company;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

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
}
