<?php

namespace App\Infrastructure\Admin\Http;

use App\Domain\Subscription\PaymentMethodEnum;
use App\Domain\Subscription\PaymentStatusEnum;
use App\Infrastructure\Subscription\Entity\Subscription;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

/**
 * @extends AbstractCrudController<Subscription>
 */
class SubscriptionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Subscription::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('company', 'Entreprise')
                ->setFormTypeOption('choice_label', 'name')
                ->setRequired(true),
            DateTimeField::new('startDate', 'Date de début'),
            DateTimeField::new('endDate', 'Date de fin'),
            AssociationField::new('subscriptionPlan', 'Plan')
                ->setRequired(true),
            ChoiceField::new('paymentMethod', 'Moyen de paiement')
                ->setChoices([
                    'Stripe' => PaymentMethodEnum::STRIPE,
                    'Mobile Money' => PaymentMethodEnum::MOBILE_MONEY,
                ])
                ->setRequired(false),
            ChoiceField::new('paymentStatus', 'Statut du paiement')
                ->setChoices([
                    'Incomplet' => PaymentStatusEnum::INCOMPLETE,
                    'En attente de vérification' => PaymentStatusEnum::PENDING_REVIEW,
                    'Payé' => PaymentStatusEnum::PAID,
                    'Échéance dépassée' => PaymentStatusEnum::PAST_DUE,
                    'Échoué' => PaymentStatusEnum::FAILED,
                    'Impayé' => PaymentStatusEnum::UNPAID,
                    'Remboursé' => PaymentStatusEnum::REFUNDED,
                    'Annulé' => PaymentStatusEnum::VOIDED,
                    'Non requis' => PaymentStatusEnum::NOT_REQUIRED,
                ])
                ->setRequired(false),
        ];
    }
}
