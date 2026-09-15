<?php

namespace App\Tests\Infrastructure\User\Security;

use App\Infrastructure\User\Entity\User;
use App\Infrastructure\User\Security\ActiveUserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

class ActiveUserCheckerTest extends TestCase
{
    public function testCheckPreAuthThrowsWhenTheAppUserIsNotActive(): void
    {
        $user = (new User())->setEmail('jane@example.com')->setIsActive(false);

        $this->expectException(CustomUserMessageAccountStatusException::class);

        (new ActiveUserChecker())->checkPreAuth($user);
    }

    public function testCheckPreAuthAllowsAnActiveAppUser(): void
    {
        $user = (new User())->setEmail('jane@example.com')->setIsActive(true);

        (new ActiveUserChecker())->checkPreAuth($user);

        $this->addToAssertionCount(1);
    }

    public function testCheckPreAuthIgnoresNonAppUserImplementations(): void
    {
        $user = $this->createStub(UserInterface::class);

        (new ActiveUserChecker())->checkPreAuth($user);

        $this->addToAssertionCount(1);
    }

    public function testCheckPostAuthIsANoOp(): void
    {
        (new ActiveUserChecker())->checkPostAuth($this->createStub(UserInterface::class));

        $this->addToAssertionCount(1);
    }
}
