<?php

namespace App\Tests\Infrastructure\User\Http;

use App\Infrastructure\Security\Keycloak\ApiUser;
use App\Infrastructure\User\Http\GetCurrentUserController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class GetCurrentUserControllerTest extends TestCase
{
    public function testReturnsTheProfileOfTheToken(): void
    {
        $user = new ApiUser(
            'keycloak-id',
            'user-id',
            null,
            ['ROLE_USER', 'ROLE_COMPANY_ADMIN'],
            email: 'jane@example.com',
            firstName: 'Jane',
            lastName: 'Doe',
            phoneNumber: '+261340000000',
        );

        $response = (new GetCurrentUserController())($user);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame([
            'id' => 'user-id',
            'email' => 'jane@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone_number' => '+261340000000',
            'company_id' => null,
            'roles' => ['ROLE_USER', 'ROLE_COMPANY_ADMIN'],
        ], json_decode((string) $response->getContent(), true)['payload'] ?? null);
    }
}
