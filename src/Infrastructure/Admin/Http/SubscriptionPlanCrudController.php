<?php

namespace App\Infrastructure\Admin\Http;

use App\Domain\Subscription\PlanEnum;
use App\Infrastructure\Subscription\Entity\SubscriptionPlan;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class SubscriptionPlanCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SubscriptionPlan::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            ChoiceField::new('plan', 'Plan')
                ->setChoices([
                    'Free' => PlanEnum::FREE,
                    'Standard' => PlanEnum::STANDARD,
                    'Pro' => PlanEnum::PRO,
                    'Premium' => PlanEnum::PREMIUM,
                ])
                ->setRequired(true),
            IntegerField::new('storage', 'Stockage (octets)')
                ->setRequired(true),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Liste de plans d\'abonnement')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter un plan')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier le plan')
        ;
    }
}
