<?php

namespace App\Infrastructure\Admin\Http;

use App\Infrastructure\User\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            TextField::new('email'),
            TextField::new('firstName', 'Prénom'),
            TextField::new('lastName', 'Nom'),
            TextField::new('phoneNumber', 'Numéro de téléphone'),
            TextField::new('password', 'Mot de passe')
                ->onlyOnForms()
                ->setFormTypeOptions([
                    'required' => $pageName === Crud::PAGE_NEW,
                ]),
        ];

            if ($pageName === Crud::PAGE_EDIT) {
                array_splice($fields, 1, 0, [
                    ChoiceField::new('role', 'Rôle')
                        ->setChoices([
                            'Admin' => 'ROLE_ADMIN',
                            'Prestataire' => 'ROLE_COMPANY_ADMIN',
                            'Utilisateur' => 'ROLE_USER',
                        ])
                        ->setFormTypeOptions(['required' => true]),
                ]);
            }

        return $fields;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Liste des utilisateurs')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter un utilisateur')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier l\'utilisateur')
        ;
    }
}
