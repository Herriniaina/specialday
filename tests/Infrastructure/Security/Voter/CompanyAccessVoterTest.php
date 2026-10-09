<?php

namespace App\Tests\Infrastructure\Security\Voter;

use App\Domain\Company\Company;
use App\Infrastructure\Company\Http\CompanySubdomainListener;
use App\Infrastructure\Security\Keycloak\ApiUser;
use App\Infrastructure\Security\Voter\CompanyAccessVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

class CompanyAccessVoterTest extends TestCase
{
    private CompanyAccessVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new CompanyAccessVoter();
    }

    public function testAbstainsOnOtherAttributes(): void
    {
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($this->token('company-id'), $this->request('company-id'), ['ROLE_USER']));
    }

    public function testAbstainsOnOtherSubjects(): void
    {
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter->vote($this->token('company-id'), $this->company('company-id'), [CompanyAccessVoter::ACCESS]));
    }

    public function testGrantsAnApiUserOnTheMainHost(): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($this->token(null), new Request(), [CompanyAccessVoter::ACCESS]));
    }

    public function testDeniesAnonymousOnTheMainHost(): void
    {
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote(new NullToken(), new Request(), [CompanyAccessVoter::ACCESS]));
    }

    public function testGrantsAMemberOfTheSubdomainCompany(): void
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($this->token('company-id'), $this->request('company-id'), [CompanyAccessVoter::ACCESS]));
    }

    public function testDeniesAMemberOfAnotherCompany(): void
    {
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($this->token('other-company-id'), $this->request('company-id'), [CompanyAccessVoter::ACCESS]));
    }

    public function testDeniesAUserWithoutCompany(): void
    {
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($this->token(null), $this->request('company-id'), [CompanyAccessVoter::ACCESS]));
    }

    public function testDeniesAnonymousAndNonApiUsers(): void
    {
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote(new NullToken(), $this->request('company-id'), [CompanyAccessVoter::ACCESS]));

        $backOfficeUser = new UsernamePasswordToken(new InMemoryUser('admin', null, ['ROLE_ADMIN']), 'admin', ['ROLE_ADMIN']);
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($backOfficeUser, $this->request('company-id'), [CompanyAccessVoter::ACCESS]));
    }

    private function token(?string $companyId): UsernamePasswordToken
    {
        $user = new ApiUser('keycloak-id', 'user-id', $companyId, ['ROLE_USER']);

        return new UsernamePasswordToken($user, 'api', $user->getRoles());
    }

    private function request(string $companyId): Request
    {
        $request = new Request();
        $request->attributes->set(CompanySubdomainListener::ATTRIBUTE, $this->company($companyId));

        return $request;
    }

    private function company(string $id): Company
    {
        return new Company($id, 'Photo Company', 'photo-company', new \DateTimeImmutable());
    }
}
