<?php

namespace App\Infrastructure\Subscription\Http;

use App\Application\Subscription\CreateSubscription;
use App\Application\Subscription\CreateSubscriptionRequest;
use App\Application\Subscription\SubscriptionAlreadyExistsException;
use App\Domain\Company\CompanyNotFoundException;
use App\Domain\Subscription\PaymentMethodEnum;
use App\Domain\Subscription\PlanEnum;
use App\Infrastructure\Shared\Http\ApiAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class CreateSubscriptionController extends ApiAbstractController
{
    public function __construct(
        private CreateSubscription $createSubscription,
    ) {
    }

    #[Route('/api/public/subscription', name: 'app_subscription_create', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] CreateSubscriptionInput $input): JsonResponse
    {
        try {
            $id = $this->createSubscription->execute(new CreateSubscriptionRequest(
                companyId: $input->companyId,
                plan: PlanEnum::from($input->plan),
                paymentMethod: null !== $input->paymentMethod ? PaymentMethodEnum::from($input->paymentMethod) : null,
            ));
        } catch (CompanyNotFoundException $e) {
            return $this->notFound($e->getMessage());
        } catch (SubscriptionAlreadyExistsException $e) {
            return $this->conflict($e->getMessage());
        } catch (\Exception) {
            return $this->error();
        }

        return $this->created(['id' => $id]);
    }
}
