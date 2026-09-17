<?php

namespace App\Infrastructure\Shared\Doctrine;

use App\Application\Shared\TransactionInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineTransaction implements TransactionInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[\Override]
    public function transactional(callable $callback): mixed
    {
        return $this->em->wrapInTransaction($callback);
    }
}
