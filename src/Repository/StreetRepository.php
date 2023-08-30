<?php

declare(strict_types=1);

namespace Crovitche\SwissGeoBundle\Repository;

use Crovitche\SwissGeoBundle\Entity\{BuildingAddress, Street};
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\{Query, QueryBuilder};
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author  Thibault Gattolliat
 *
 * @extends ServiceEntityRepository<Street>
 *
 * @method  Street|null  find($id, $lockMode = null, $lockVersion = null)
 * @method  Street|null  findOneBy(array $criteria, array $orderBy = null)
 * @method  Street[]     findAll()
 * @method  Street[]     findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StreetRepository extends ServiceEntityRepository
{
    private const LIMIT = 7;

    /**
     * {@inheritDoc}
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Street::class);
    }

    public function whereAssociatedAddressesHavingNumbers(QueryBuilder $qb): void
    {
        $qbExist = $this->_em->createQueryBuilder();

        $qb->andWhere($qb->expr()->exists(
            $qbExist
                ->select('1')
                ->from(BuildingAddress::class, 'a0')
                ->where($qb->expr()->eq('a0.streetLocality', 's1.id'))
                ->andWhere($qb->expr()->isNotNull('a0.number'))
                ->getDQL()
        ));
    }

    public function findAllByLocality(
        int $localityId,
        ?string $name = null
    ): Collection {
        $qb = $this->getFindAllByLocalityQueryBuilder($localityId, $name, isSearchQuery: true);
        $query = $qb->getQuery();
        $query->setHint(Query::HINT_READ_ONLY, true);

        return new ArrayCollection($query->getResult());
    }

    public function getFindAllByLocalityQueryBuilder(
        int $localityId,
        ?string $streetName = null,
        bool $isSearchQuery = false
    ): QueryBuilder {
        $qb = $this->_em->createQueryBuilder();
        $qb
            ->select('s0')
            ->from(Street::class, 's0')
            ->innerJoin('s0.streetLocality', 's1')
            ->innerJoin('s1.locality', 'l1')
            ->andWhere("s0.type = 'street'")
            ->andWhere("s0.status <> 'outdated'")
            ->andWhere('l1.id = :locality_id')
            ->setParameter('locality_id', $localityId, Types::INTEGER);

        $this->whereAssociatedAddressesHavingNumbers($qb);

        if ($isSearchQuery) {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->isNull(':street_name'),
                        $qb->expr()->like('s0.name', ':street_name')
                    )
                )
                ->setParameter('street_name', '%'.$streetName.'%', Types::STRING)
                ->orderBy('s0.name')
                ->setMaxResults(self::LIMIT)
            ;
        }

        return $qb;
    }
}
