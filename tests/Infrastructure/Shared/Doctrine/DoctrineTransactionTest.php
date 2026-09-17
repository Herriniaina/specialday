<?php

namespace App\Tests\Infrastructure\Shared\Doctrine;

use App\Infrastructure\Shared\Doctrine\DoctrineTransaction;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DoctrineTransactionTest extends TestCase
{
    public function testTransactionalDelegatesToEntityManagerWrapInTransaction(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $callback = static fn (): string => 'result';

        $em->expects(self::once())
            ->method('wrapInTransaction')
            ->with($callback)
            ->willReturn('result');

        $transaction = new DoctrineTransaction($em);

        self::assertSame('result', $transaction->transactional($callback));
    }
}
