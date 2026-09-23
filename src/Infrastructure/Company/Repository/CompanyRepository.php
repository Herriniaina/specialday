<?php

namespace App\Infrastructure\Company\Repository;

use App\Domain\Company\Company;
use App\Domain\Company\CompanyRepositoryInterface;
use App\Domain\Company\CompanySearchCriteria;
use App\Domain\Company\CompanySearchResult;
use App\Domain\Shared\Filter;
use App\Domain\Shared\FilterOperator;
use App\Infrastructure\Company\Entity\Company as CompanyEntity;
use App\Infrastructure\Prestation\Entity\Prestation as PrestationEntity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class CompanyRepository implements CompanyRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[\Override]
    public function save(Company $company): void
    {
        $entity = ($this->em->find(CompanyEntity::class, $company->id) ?? new CompanyEntity())
            ->setId($company->id)
            ->setName($company->name)
            ->setSlug($company->slug)
            ->setCreatedAt($company->createdAt)
            ->setNif($company->nif)
            ->setStat($company->stat)
            ->setRcs($company->rcs)
            ->setPhone($company->phone)
            ->setInstagramProfile($company->instagramProfile)
            ->setFacebookProfile($company->facebookProfile)
            ->setYoutubeProfile($company->youtubeProfile);

        $this->em->persist($entity);
        $this->em->flush();
    }

    #[\Override]
    public function existsBySlug(string $slug): bool
    {
        return null !== $this->em->getRepository(CompanyEntity::class)
            ->findOneBy([
                'slug' => $slug,
            ]);
    }

    #[\Override]
    public function existsById(string $id): bool
    {
        return null !== $this->em->find(CompanyEntity::class, $id);
    }

    #[\Override]
    public function search(CompanySearchCriteria $criteria): CompanySearchResult
    {
        $qb = $this->em->createQueryBuilder()
            ->select('c')
            ->from(CompanyEntity::class, 'c');

        $this->applyFilters($qb, $criteria);

        $total = (int) (clone $qb)
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $qb->orderBy('c.name', 'ASC')
            ->setFirstResult(($criteria->page - 1) * $criteria->limit)
            ->setMaxResults($criteria->limit);

        /** @var CompanyEntity[] $entities */
        $entities = $qb->getQuery()->getResult();

        return new CompanySearchResult(
            items: array_map($this->toDomain(...), $entities),
            total: $total,
            page: $criteria->page,
            limit: $criteria->limit,
        );
    }

    private function applyFilters(QueryBuilder $qb, CompanySearchCriteria $criteria): void
    {
        if (null !== $criteria->nameFilter) {
            $this->applyNameFilter($qb, $criteria->nameFilter);
        }

        if (null !== $criteria->prestationFilter) {
            $this->applyPrestationFilter($qb, $criteria->prestationFilter);
        }

        if (null !== $criteria->notesFilter) {
            $this->applyNotesFilter($qb, $criteria->notesFilter);
        }
    }

    private function applyNameFilter(QueryBuilder $qb, Filter $filter): void
    {
        match ($filter->operator) {
            FilterOperator::EQ => $qb->andWhere('c.name = :companyName')->setParameter('companyName', $filter->values[0]),
            FilterOperator::NOT_EQ => $qb->andWhere('c.name != :companyName')->setParameter('companyName', $filter->values[0]),
            FilterOperator::IN => $qb->andWhere('c.name IN (:companyNames)')->setParameter('companyNames', $filter->values),
            FilterOperator::NOT_IN => $qb->andWhere('c.name NOT IN (:companyNames)')->setParameter('companyNames', $filter->values),
            FilterOperator::CONTAINS => $qb->andWhere('c.name LIKE :companyName')->setParameter('companyName', '%'.$filter->values[0].'%'),
        };
    }

    private function applyPrestationFilter(QueryBuilder $qb, Filter $filter): void
    {
        $sub = $this->em->createQueryBuilder()
            ->select('1')
            ->from(PrestationEntity::class, 'p')
            ->join('p.prestationCategory', 'pc')
            ->andWhere('p.company = c')
            ->andWhere('pc.title IN (:prestationTitles)');

        $exists = $qb->expr()->exists($sub->getDQL());

        $isExclusion = \in_array($filter->operator, [FilterOperator::NOT_EQ, FilterOperator::NOT_IN], true);

        $qb->andWhere($isExclusion ? $qb->expr()->not($exists) : $exists)
            ->setParameter('prestationTitles', $filter->values);
    }

    private function applyNotesFilter(QueryBuilder $qb, Filter $filter): void
    {
        $sub = $this->em->createQueryBuilder()
            ->select('1')
            ->from(PrestationEntity::class, 'p')
            ->andWhere('p.company = c')
            ->andWhere('p.notes LIKE :notesContains');

        $qb->andWhere($qb->expr()->exists($sub->getDQL()))
            ->setParameter('notesContains', '%'.self::escapeLikeValue($filter->values[0]).'%');
    }

    private static function escapeLikeValue(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function toDomain(CompanyEntity $entity): Company
    {
        return new Company(
            id: (string) $entity->getId(),
            name: (string) $entity->getName(),
            slug: (string) $entity->getSlug(),
            createdAt: $entity->getCreatedAt() ?? new \DateTimeImmutable(),
            nif: $entity->getNif(),
            stat: $entity->getStat(),
            rcs: $entity->getRcs(),
            phone: $entity->getPhone(),
            instagramProfile: $entity->getInstagramProfile(),
            facebookProfile: $entity->getFacebookProfile(),
            youtubeProfile: $entity->getYoutubeProfile(),
        );
    }
}
