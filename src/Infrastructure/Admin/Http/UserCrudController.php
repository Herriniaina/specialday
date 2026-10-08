<?php

namespace App\Infrastructure\Admin\Http;

use App\Infrastructure\User\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Links between Keycloak accounts and companies. The profile (email, name, phone), the password
 * and the roles are managed in the Keycloak console; a user cannot be created from here (it would
 * have no account to sign in with).
 *
 * @extends AbstractCrudController<User>
 */
class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('identityProviderId', 'Identifiant Keycloak')->setDisabled(),
            AssociationField::new('company', 'Société'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Liste des utilisateurs')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier l\'utilisateur')
        ;
    }
}
