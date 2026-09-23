<?php

namespace App\Infrastructure\PrestationCategory\Repository;

use App\Infrastructure\PrestationCategory\Entity\PrestationCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrestationCategory>
 */
class PrestationCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrestationCategory::class);
    }
}
